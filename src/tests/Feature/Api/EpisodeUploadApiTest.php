<?php

namespace Tests\Feature\Api;

use App\ApiTokens\ApiTokenService;
use App\Models\Episode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @internal
 */
class EpisodeUploadApiTest extends TestCase
{
    use RefreshDatabase;

    public function testUnauthenticatedRequestIsRejected(): void
    {
        $this->postJson('/api/episodes')
            ->assertUnauthorized();
    }

    public function testTokenWithoutEpisodesWriteAbilityIsRejected(): void
    {
        $plainTextToken = User::factory()
            ->create()
            ->createToken('playpipe-api', [ApiTokenService::ABILITY_EPISODES_READ])
            ->plainTextToken;

        $this->withToken($plainTextToken)
            ->post('/api/episodes', $this->validPayload())
            ->assertForbidden();
    }

    public function testValidUploadStoresEpisodeRowsAndObjects(): void
    {
        Storage::fake('s3');
        config(['playpipe.upload.storage_disk' => 's3']);

        $this->withWriteToken()
            ->post('/api/episodes', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('episode.episode_key', 'sample-episode')
            ->assertJsonPath('episode.status', Episode::STATUS_AVAILABLE)
            ->assertJsonPath('episode.title', 'サンプルエピソード')
            ->assertJsonPath('episode.language', 'ja')
            ->assertJsonPath('episode.audio.disk', 's3')
            ->assertJsonPath('episode.audio.path', 'episodes/sample-episode/audio.mp3')
            ->assertJsonPath('episode.audio.duration_seconds', 90)
            ->assertJsonPath('episode.sections_count', 2)
            ->assertJsonPath('episode.topics_count', 1);

        self::assertDatabaseHas('episodes', [
            'episode_key' => 'sample-episode',
            'status' => Episode::STATUS_AVAILABLE,
            'title' => 'サンプルエピソード',
            'language' => 'ja',
            'character_key' => 'neko_nyan_balanced_radio',
            'character_name' => 'ねこにゃん',
            'audio_disk' => 's3',
            'audio_path' => 'episodes/sample-episode/audio.mp3',
            'audio_duration_seconds' => 90,
            'voicepipe_version' => 'voicepipe-test',
        ]);
        self::assertDatabaseHas('episode_sections', [
            'section_type' => 'opening',
            'title' => 'オープニング',
            'sort_order' => 1,
        ]);
        self::assertDatabaseHas('episode_topics', [
            'topic_id' => 'upstream:236',
            'status' => 'used_in_scenario',
            'title' => 'GitHubアカウントのセキュリティ設定を点検するCLI「Moat」',
            'source_name' => 'Laravel News',
            'sort_order' => 1,
        ]);

        $episode = Episode::query()->with(['sections', 'topics'])->firstOrFail();
        $renderMetadata = $episode->getAttribute('render_metadata_json');
        self::assertIsArray($renderMetadata);
        self::assertSame('speaker-a', $renderMetadata['speaker'] ?? null);
        self::assertSame('こんにちは。', $episode->sections->first()?->text);
        self::assertSame('GitHubの設定を読み取り専用で点検するCLIツールです。', $episode->topics->first()?->summary);

        Storage::disk('s3')->assertExists('episodes/sample-episode/audio.mp3');
        Storage::disk('s3')->assertExists('episodes/sample-episode/episode.json');
    }

    public function testEpisodeJsonCanBeUploadedAsFile(): void
    {
        Storage::fake('s3');
        config(['playpipe.upload.storage_disk' => 's3']);

        $this->withWriteToken()
            ->post('/api/episodes', [
                'audio' => $this->fakeMp3(),
                'episode_json' => UploadedFile::fake()->createWithContent(
                    'episode.json',
                    $this->sampleEpisodeJson(),
                ),
            ])
            ->assertCreated()
            ->assertJsonPath('episode.episode_key', 'sample-episode');

        self::assertDatabaseCount('episodes', 1);
        Storage::disk('s3')->assertExists('episodes/sample-episode/episode.json');
    }

    public function testDuplicateEpisodeKeyReturnsConflict(): void
    {
        Storage::fake('s3');
        config(['playpipe.upload.storage_disk' => 's3']);

        $this->withWriteToken()
            ->post('/api/episodes', $this->validPayload())
            ->assertCreated();

        $this->withWriteToken()
            ->post('/api/episodes', $this->validPayload())
            ->assertConflict()
            ->assertJsonPath('message', 'Episode already exists.')
            ->assertJsonPath('episode_key', 'sample-episode');
    }

    public function testInvalidEpisodeJsonReturnsValidationError(): void
    {
        Storage::fake('s3');
        config(['playpipe.upload.storage_disk' => 's3']);

        $this->withWriteToken()
            ->post('/api/episodes', [
                'audio' => $this->fakeMp3(),
                'episode_json' => '{"episode":',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('episode_json');
    }

    public function testMissingRequiredSectionFieldReturnsValidationError(): void
    {
        Storage::fake('s3');
        config(['playpipe.upload.storage_disk' => 's3']);

        $payload = $this->sampleEpisodePayload();
        $episode = $payload['episode'] ?? null;
        self::assertIsArray($episode);
        $scenarioJson = $episode['scenario_json'] ?? null;
        self::assertIsArray($scenarioJson);
        $sections = $scenarioJson['sections'] ?? null;
        self::assertIsArray($sections);
        $firstSection = $sections[0] ?? null;
        self::assertIsArray($firstSection);
        unset($firstSection['text']);
        $sections[0] = $firstSection;
        $scenarioJson['sections'] = $sections;
        $episode['scenario_json'] = $scenarioJson;
        $payload['episode'] = $episode;

        $this->withWriteToken()
            ->post('/api/episodes', [
                'audio' => $this->fakeMp3(),
                'episode_json' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('episode_json');
    }

    private function withWriteToken(?string $plainTextToken = null): self
    {
        if ($plainTextToken === null) {
            $plainTextToken = User::factory()
                ->create()
                ->createToken('playpipe-api', [ApiTokenService::ABILITY_EPISODES_WRITE])
                ->plainTextToken;
        }

        return $this->withHeader('Accept', 'application/json')
            ->withToken($plainTextToken);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'audio' => $this->fakeMp3(),
            'episode_json' => $this->sampleEpisodeJson(),
            'audio_duration_seconds' => 90,
            'recorded_at' => '2026-05-29T07:10:00+09:00',
            'voicepipe_version' => 'voicepipe-test',
            'render_metadata_json' => json_encode(['speaker' => 'speaker-a'], JSON_THROW_ON_ERROR),
        ];
    }

    private function fakeMp3(): UploadedFile
    {
        return UploadedFile::fake()->create('sample.mp3', 12, 'audio/mpeg');
    }

    private function sampleEpisodeJson(): string
    {
        $json = file_get_contents(base_path('tests/Fixtures/episodes/sample-episode.json'));

        self::assertIsString($json);

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleEpisodePayload(): array
    {
        $payload = json_decode($this->sampleEpisodeJson(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);

        /** @var array<string, mixed> $payload */
        return $payload;
    }
}
