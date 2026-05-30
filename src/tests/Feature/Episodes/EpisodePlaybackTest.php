<?php

namespace Tests\Feature\Episodes;

use App\Models\Episode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 */
class EpisodePlaybackTest extends TestCase
{
    use RefreshDatabase;

    public function testOldEpisodeIndexRouteRedirectsToListenArchive(): void
    {
        $this->actingAs($this->allowedUser())
            ->get('/episodes')
            ->assertRedirect(route('listen.episodes.index'));
    }

    public function testOldEpisodeDetailRouteRedirectsToListenDetail(): void
    {
        $episode = Episode::factory()->create([
            'episode_key' => 'episode-playback',
        ]);

        $this->actingAs($this->allowedUser())
            ->get("/episodes/{$episode->episode_key}")
            ->assertRedirect(route('listen.episodes.show', $episode));
    }

    public function testOldEpisodeAudioRouteRedirectsToListenAudioRoute(): void
    {
        $episode = Episode::factory()->create([
            'episode_key' => 'episode-playback',
        ]);

        $this->actingAs($this->allowedUser())
            ->get("/episodes/{$episode->episode_key}/audio")
            ->assertRedirect(route('listen.episodes.audio', $episode));
    }

    public function testOldEpisodeDownloadRouteRedirectsToListenDownloadRoute(): void
    {
        $episode = Episode::factory()->create([
            'episode_key' => 'episode-playback',
        ]);

        $this->actingAs($this->allowedUser())
            ->get("/episodes/{$episode->episode_key}/download")
            ->assertRedirect(route('listen.episodes.download', $episode));
    }

    private function allowedUser(): User
    {
        config(['playpipe.admin.allowed_emails' => ['listener@example.test']]);

        return User::factory()->create([
            'email' => 'listener@example.test',
        ]);
    }
}
