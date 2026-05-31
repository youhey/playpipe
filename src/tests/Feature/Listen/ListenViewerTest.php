<?php

namespace Tests\Feature\Listen;

use App\Models\Episode;
use App\Models\EpisodeSection;
use App\Models\EpisodeTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @internal
 */
class ListenViewerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function testGuestCannotAccessListenHome(): void
    {
        $this->get('/listen')
            ->assertRedirect('/login');
    }

    public function testAuthenticatedAllowedUserCanAccessListenHome(): void
    {
        $episode = $this->episodeWithContent();

        $this->actingAs($this->allowedUser())
            ->get('/listen')
            ->assertOk()
            ->assertSee('Transmission_Log')
            ->assertSee($episode->title);
    }

    public function testDisallowedAuthenticatedUserCannotAccessListenHome(): void
    {
        config(['playpipe.admin.allowed_emails' => ['listener@example.test']]);

        $this->actingAs(User::factory()->create(['email' => 'other@example.test']))
            ->get('/listen')
            ->assertForbidden();
    }

    public function testAuthenticatedAllowedUserCanAccessListenEpisodes(): void
    {
        $episode = $this->episodeWithContent();

        $this->actingAs($this->allowedUser())
            ->get('/listen/episodes')
            ->assertOk()
            ->assertSee('Encrypted Feed')
            ->assertSee($episode->title)
            ->assertSee('Protocol_Home')
            ->assertSee('Open Protocol')
            ->assertSee('Published')
            ->assertSee('Recorded')
            ->assertSee('Duration')
            ->assertSee('15:00')
            ->assertSee('128,000 bytes')
            ->assertDontSee('player-frame', false)
            ->assertDontSee('waveform', false);
    }

    public function testListenEpisodesSupportsSearchAndCharacterFilter(): void
    {
        $episode = $this->episodeWithContent(['episode_key' => 'episode-visible']);
        $this->episodeWithContent([
            'episode_key' => 'episode-hidden',
            'title' => '別のエピソード',
            'character_key' => 'other_character',
            'character_name' => '別キャラクター',
        ]);

        $this->actingAs($this->allowedUser())
            ->get('/listen/episodes?q=visible&character=neko_nyan_balanced_radio')
            ->assertOk()
            ->assertSee($episode->episode_key)
            ->assertDontSee('episode-hidden');
    }

    public function testAuthenticatedAllowedUserCanAccessListenEpisodeDetail(): void
    {
        $episode = $this->episodeWithContent();

        $this->actingAs($this->allowedUser())
            ->get("/listen/episodes/{$episode->episode_key}")
            ->assertOk()
            ->assertSee('Episode_Detail')
            ->assertSee(route('listen.episodes.audio', $episode), false)
            ->assertSee(route('listen.episodes.download', $episode), false)
            ->assertSee('今日のトピックを紹介します。')
            ->assertSee('GitHub アカウントのセキュリティ設定を点検する CLI')
            ->assertSee('GitHub の設定を読み取り専用で点検する CLI ツールです。')
            ->assertSee('Laravel News')
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('data-listen-player', false)
            ->assertSee('waveform-visualizer', false)
            ->assertSee('data-duration-seconds="900"', false)
            ->assertSee('data-section-list', false)
            ->assertSee('data-section', false)
            ->assertSee('data-start-seconds="0"', false)
            ->assertSee('data-end-seconds="60"', false)
            ->assertSee('00:00 / 15:00')
            ->assertDontSee('episode_json');
    }

    public function testListenAudioRouteRequiresLogin(): void
    {
        $episode = $this->episodeWithContent();

        $this->get("/listen/episodes/{$episode->episode_key}/audio")
            ->assertRedirect('/login');
    }

    public function testAuthenticatedAllowedUserCanReadListenAudioResponse(): void
    {
        Storage::fake('s3');
        $episode = $this->episodeWithContent();
        Storage::disk('s3')->put($episode->audio_path, 'fake mp3 bytes');

        $response = $this->actingAs($this->allowedUser())
            ->get("/listen/episodes/{$episode->episode_key}/audio");

        $status = $response->baseResponse->getStatusCode();

        if ($status === 200) {
            $response->assertHeader('content-type', 'audio/mpeg');

            return;
        }

        self::assertTrue($response->baseResponse->isRedirection());
    }

    public function testListenDownloadRouteRequiresLogin(): void
    {
        $episode = $this->episodeWithContent();

        $this->get("/listen/episodes/{$episode->episode_key}/download")
            ->assertRedirect('/login');
    }

    public function testAuthenticatedAllowedUserCanDownloadListenAudio(): void
    {
        Storage::fake('s3');
        $episode = $this->episodeWithContent();
        Storage::disk('s3')->put($episode->audio_path, 'fake mp3 bytes');

        $response = $this->actingAs($this->allowedUser())
            ->get("/listen/episodes/{$episode->episode_key}/download");

        $response->assertOk()
            ->assertHeader('content-type', 'audio/mpeg');

        self::assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        self::assertStringContainsString($episode->episode_key . '.mp3', (string) $response->headers->get('content-disposition'));
    }

    public function testMissingListenEpisodeReturnsNotFound(): void
    {
        $this->actingAs($this->allowedUser())
            ->get('/listen/episodes/missing-episode')
            ->assertNotFound();
    }

    public function testUnavailableEpisodeIsHiddenFromListenViewer(): void
    {
        $episode = $this->episodeWithContent(['status' => 'archived']);

        $this->actingAs($this->allowedUser())
            ->get('/listen/episodes')
            ->assertOk()
            ->assertDontSee($episode->episode_key);

        $this->actingAs($this->allowedUser())
            ->get("/listen/episodes/{$episode->episode_key}")
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
            'summary' => 'GitHub の設定を読み取り専用で点検する CLI ツールです。',
            'source_name' => 'Laravel News',
            'sort_order' => 1,
        ]);

        return $episode;
    }

    private function allowedUser(): User
    {
        $email = 'listener-' . Str::uuid() . '@example.test';
        config(['playpipe.admin.allowed_emails' => [$email]]);

        return User::factory()->create([
            'email' => $email,
        ]);
    }
}
