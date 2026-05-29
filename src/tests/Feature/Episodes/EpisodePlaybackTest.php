<?php

namespace Tests\Feature\Episodes;

use App\Models\Episode;
use App\Models\EpisodeSection;
use App\Models\EpisodeTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @internal
 */
class EpisodePlaybackTest extends TestCase
{
    use RefreshDatabase;

    public function testEpisodeIndexRequiresLogin(): void
    {
        $this->get('/episodes')
            ->assertRedirect('/login');
    }

    public function testAuthenticatedUserCanSeeEpisodeIndex(): void
    {
        $episode = $this->episodeWithContent();

        $this->actingAs(User::factory()->create())
            ->get('/episodes')
            ->assertOk()
            ->assertSee('サンプルエピソード')
            ->assertSee($episode->episode_key)
            ->assertSee('ねこにゃん');
    }

    public function testEpisodeIndexSupportsSearchAndCharacterFilter(): void
    {
        $episode = $this->episodeWithContent(['episode_key' => 'episode-visible']);
        $this->episodeWithContent([
            'episode_key' => 'episode-hidden',
            'title' => '別のエピソード',
            'character_key' => 'other_character',
            'character_name' => '別キャラクター',
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/episodes?q=visible&character=neko_nyan_balanced_radio')
            ->assertOk()
            ->assertSee($episode->episode_key)
            ->assertDontSee('episode-hidden');
    }

    public function testAuthenticatedUserCanSeeEpisodeDetail(): void
    {
        $episode = $this->episodeWithContent();

        $this->actingAs(User::factory()->create())
            ->get("/episodes/{$episode->episode_key}")
            ->assertOk()
            ->assertSee('サンプルエピソード')
            ->assertSee(route('episodes.audio', $episode), false)
            ->assertSee('今日のトピックを紹介します。')
            ->assertSee('GitHub アカウントのセキュリティ設定を点検する CLI')
            ->assertSee('Laravel News')
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function testAudioRouteRequiresLogin(): void
    {
        $episode = $this->episodeWithContent();

        $this->get("/episodes/{$episode->episode_key}/audio")
            ->assertRedirect('/login');
    }

    public function testAuthenticatedUserCanReadAudioResponse(): void
    {
        Storage::fake('s3');
        $episode = $this->episodeWithContent();
        Storage::disk('s3')->put($episode->audio_path, 'fake mp3 bytes');

        $response = $this->actingAs(User::factory()->create())
            ->get("/episodes/{$episode->episode_key}/audio");

        $status = $response->baseResponse->getStatusCode();

        if ($status === 200) {
            $response->assertHeader('content-type', 'audio/mpeg');

            return;
        }

        self::assertTrue($response->baseResponse->isRedirection());
    }

    public function testDownloadRouteRequiresLogin(): void
    {
        $episode = $this->episodeWithContent();

        $this->get("/episodes/{$episode->episode_key}/download")
            ->assertRedirect('/login');
    }

    public function testAuthenticatedUserCanDownloadAudio(): void
    {
        Storage::fake('s3');
        $episode = $this->episodeWithContent();
        Storage::disk('s3')->put($episode->audio_path, 'fake mp3 bytes');

        $response = $this->actingAs(User::factory()->create())
            ->get("/episodes/{$episode->episode_key}/download");

        $response->assertOk()
            ->assertHeader('content-type', 'audio/mpeg');

        self::assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        self::assertStringContainsString($episode->episode_key . '.mp3', (string) $response->headers->get('content-disposition'));
    }

    public function testMissingEpisodeReturnsNotFound(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/episodes/missing-episode')
            ->assertNotFound();
    }

    public function testUnavailableEpisodeIsHiddenFromPlayback(): void
    {
        $episode = $this->episodeWithContent(['status' => 'archived']);

        $this->actingAs(User::factory()->create())
            ->get('/episodes')
            ->assertOk()
            ->assertDontSee($episode->episode_key);

        $this->actingAs(User::factory()->create())
            ->get("/episodes/{$episode->episode_key}")
            ->assertNotFound();
    }

    /**
     * @param array<string, mixed> $episodeOverrides
     */
    private function episodeWithContent(array $episodeOverrides = []): Episode
    {
        /** @var Episode $episode */
        $episode = Episode::factory()->create(array_merge([
            'episode_key' => 'episode-playback',
            'title' => 'サンプルエピソード',
            'character_key' => 'neko_nyan_balanced_radio',
            'character_name' => 'ねこにゃん',
            'audio_disk' => 's3',
            'audio_path' => 'episodes/episode-playback/audio.mp3',
        ], $episodeOverrides));

        EpisodeSection::factory()->create([
            'episode_id' => $episode->id,
            'section_type' => 'topic',
            'title' => '本文',
            'text' => "今日のトピックを紹介します。\n詳しく見ていきましょう。",
            'sort_order' => 1,
        ]);

        EpisodeTopic::factory()->create([
            'episode_id' => $episode->id,
            'title' => 'GitHub アカウントのセキュリティ設定を点検する CLI',
            'source_name' => 'Laravel News',
            'sort_order' => 1,
        ]);

        return $episode;
    }
}
