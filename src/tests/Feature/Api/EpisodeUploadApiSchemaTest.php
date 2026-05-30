<?php

namespace Tests\Feature\Api;

use App\ApiTokens\ApiTokenService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\AssertionFailedError;
use Tests\Support\OpenApiSchemaValidator;
use Tests\TestCase;

/**
 * @internal
 */
class EpisodeUploadApiSchemaTest extends TestCase
{
    use RefreshDatabase;

    private OpenApiSchemaValidator $openApi;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);

        $this->openApi = OpenApiSchemaValidator::fromFile(base_path('../docs/openapi.yaml'));
    }

    public function testSuccessResponseMatchesOpenApiSchema(): void
    {
        Storage::fake('s3');
        config(['playpipe.upload.storage_disk' => 's3']);

        $response = $this->withWriteToken()
            ->post('/api/episodes', $this->validUploadPayload())
            ->assertCreated();

        $this->openApi->validateComponent('EpisodeUploadResponse', $response->json());
        $this->assertResponseDoesNotExposeInternalFields($response->json());
    }

    public function testDuplicateResponseMatchesOpenApiSchema(): void
    {
        Storage::fake('s3');
        config(['playpipe.upload.storage_disk' => 's3']);

        $this->withWriteToken()
            ->post('/api/episodes', $this->validUploadPayload())
            ->assertCreated();

        $response = $this->withWriteToken()
            ->post('/api/episodes', $this->validUploadPayload())
            ->assertConflict();

        $this->openApi->validateComponent('DuplicateEpisodeResponse', $response->json());
    }

    public function testValidationErrorResponseMatchesOpenApiSchema(): void
    {
        Storage::fake('s3');
        config(['playpipe.upload.storage_disk' => 's3']);

        $response = $this->withWriteToken()
            ->post('/api/episodes', [
                'audio' => $this->fakeMp3(),
                'episode_json' => '{"episode":',
            ])
            ->assertUnprocessable();

        $this->openApi->validateComponent('ValidationErrorResponse', $response->json());
    }

    public function testUnauthenticatedErrorResponseMatchesOpenApiSchema(): void
    {
        $response = $this->withHeader('Accept', 'application/json')
            ->post('/api/episodes', [])
            ->assertUnauthorized();

        $this->openApi->validateComponent('ErrorResponse', $response->json());
    }

    public function testForbiddenErrorResponseMatchesOpenApiSchema(): void
    {
        Storage::fake('s3');
        config(['playpipe.upload.storage_disk' => 's3']);

        $plainTextToken = User::factory()
            ->create()
            ->createToken('playpipe-api', [ApiTokenService::ABILITY_EPISODES_READ])
            ->plainTextToken;

        $response = $this->withHeader('Accept', 'application/json')
            ->withToken($plainTextToken)
            ->post('/api/episodes', $this->validUploadPayload())
            ->assertForbidden();

        $this->openApi->validateComponent('ErrorResponse', $response->json());
    }

    public function testMinimalVoicepipePayloadMatchesRadiopipeEpisodePayloadSchema(): void
    {
        $this->openApi->validateComponent(
            'RadiopipeEpisodePayload',
            $this->fixturePayload('minimal-voicepipe-episode.json'),
        );
    }

    public function testTopicRichUploadPayloadMatchesRadiopipeEpisodePayloadSchema(): void
    {
        $this->openApi->validateComponent(
            'RadiopipeEpisodePayload',
            $this->fixturePayload('sample-upload-episode.json'),
        );
    }

    public function testEpisodePayloadMissingEpisodeKeyFailsSchemaValidation(): void
    {
        $payload = $this->fixturePayload('minimal-voicepipe-episode.json');
        $episode = $payload['episode'] ?? null;
        self::assertIsArray($episode);
        unset($episode['episode_key']);
        $payload['episode'] = $episode;

        $this->assertPayloadFailsSchema($payload);
    }

    public function testEpisodePayloadMissingSectionsFailsSchemaValidation(): void
    {
        $payload = $this->fixturePayload('minimal-voicepipe-episode.json');
        $episode = $payload['episode'] ?? null;
        self::assertIsArray($episode);
        $scenarioJson = $episode['scenario_json'] ?? null;
        self::assertIsArray($scenarioJson);
        unset($scenarioJson['sections']);
        $episode['scenario_json'] = $scenarioJson;
        $payload['episode'] = $episode;

        $this->assertPayloadFailsSchema($payload);
    }

    public function testEpisodePayloadSectionMissingTextFailsSchemaValidation(): void
    {
        $payload = $this->fixturePayload('minimal-voicepipe-episode.json');
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

        $this->assertPayloadFailsSchema($payload);
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
    private function validUploadPayload(): array
    {
        return [
            'audio' => $this->fakeMp3(),
            'episode_json' => $this->fixtureJson('sample-upload-episode.json'),
            'audio_duration_seconds' => 90,
            'recorded_at' => '2026-05-29T07:10:00+09:00',
            'voicepipe_version' => 'voicepipe-openapi-test',
            'render_metadata_json' => json_encode(['speaker' => 'speaker-a'], JSON_THROW_ON_ERROR),
        ];
    }

    private function fakeMp3(): UploadedFile
    {
        return UploadedFile::fake()->create('sample.mp3', 12, 'audio/mpeg');
    }

    /**
     * @return array<string, mixed>
     */
    private function fixturePayload(string $filename): array
    {
        $payload = json_decode($this->fixtureJson($filename), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    private function fixtureJson(string $filename): string
    {
        $json = file_get_contents(base_path("tests/Fixtures/episodes/{$filename}"));

        self::assertIsString($json);

        return $json;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertPayloadFailsSchema(array $payload): void
    {
        try {
            $this->openApi->validateComponent('RadiopipeEpisodePayload', $payload);
        } catch (AssertionFailedError) {
            $this->addToAssertionCount(1);

            return;
        }

        self::fail('Episode payload unexpectedly matched the documented schema.');
    }

    private function assertResponseDoesNotExposeInternalFields(mixed $payload): void
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        foreach ([
            'episode_json',
            'scenario_json',
            'render_metadata_json',
            'raw_section_json',
            'raw_topic_json',
            'storage_secret',
            'AWS_ACCESS_KEY_ID',
            'AWS_SECRET_ACCESS_KEY',
            'PLAYPIPE_API_TOKEN',
            'GOOGLE_CLIENT_SECRET',
            'api_key',
            'authorization',
            'bearer',
            'token',
        ] as $forbiddenFragment) {
            self::assertStringNotContainsString($forbiddenFragment, $json);
        }
    }
}
