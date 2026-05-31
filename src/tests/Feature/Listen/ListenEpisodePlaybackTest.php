<?php

namespace Tests\Feature\Listen;

use App\Models\Episode;
use App\Models\EpisodePlayback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @internal
 */
class ListenEpisodePlaybackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function testNewEpisodeIsUnplayedForUserWithoutPlaybackRecord(): void
    {
        $episode = Episode::factory()->create();

        $this->actingAs($this->allowedUser())
            ->get('/listen/episodes')
            ->assertOk()
            ->assertSee($episode->title)
            ->assertSee('UNPLAYED');
    }

    public function testStartingPlaybackCreatesInProgressRecord(): void
    {
        $episode = Episode::factory()->create();
        $user = $this->allowedUser();

        $this->actingAs($user)
            ->post(route('listen.episodes.playback.start', $episode))
            ->assertOk()
            ->assertJsonPath('playback.status', EpisodePlayback::STATUS_IN_PROGRESS)
            ->assertJsonPath('playback.play_count', 1);

        $this->assertDatabaseHas('episode_playbacks', [
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_IN_PROGRESS,
            'play_count' => 1,
        ]);
    }

    public function testStartingPlaybackAgainDoesNotCreateDuplicateRecord(): void
    {
        $episode = Episode::factory()->create();
        $user = $this->allowedUser();

        $this->actingAs($user)->post(route('listen.episodes.playback.start', $episode))->assertOk();
        $this->actingAs($user)
            ->post(route('listen.episodes.playback.start', $episode))
            ->assertOk()
            ->assertJsonPath('playback.status', EpisodePlayback::STATUS_IN_PROGRESS)
            ->assertJsonPath('playback.play_count', 2);

        $this->assertDatabaseCount('episode_playbacks', 1);
    }

    public function testProgressUpdatesPositionDurationAndLastPlayedAt(): void
    {
        $episode = Episode::factory()->create();
        $user = $this->allowedUser();

        $this->actingAs($user)
            ->patch(route('listen.episodes.playback.progress', $episode), [
                'position_seconds' => 120.8,
                'duration_seconds' => 900,
            ])
            ->assertOk()
            ->assertJsonPath('playback.status', EpisodePlayback::STATUS_IN_PROGRESS)
            ->assertJsonPath('playback.last_position_seconds', 120)
            ->assertJsonPath('playback.duration_seconds', 900);

        $playback = EpisodePlayback::query()->whereBelongsTo($user)->whereBelongsTo($episode)->firstOrFail();

        self::assertNotNull($playback->first_played_at);
        self::assertNotNull($playback->last_played_at);
    }

    public function testProgressClampsPositionToDuration(): void
    {
        $episode = Episode::factory()->create();

        $this->actingAs($this->allowedUser())
            ->patch(route('listen.episodes.playback.progress', $episode), [
                'position_seconds' => 999,
                'duration_seconds' => 300,
            ])
            ->assertOk()
            ->assertJsonPath('playback.last_position_seconds', 300)
            ->assertJsonPath('playback.duration_seconds', 300);
    }

    public function testCompletingPlaybackChangesStatusToCompleted(): void
    {
        $episode = Episode::factory()->create();
        $user = $this->allowedUser();

        $this->actingAs($user)->post(route('listen.episodes.playback.start', $episode))->assertOk();
        $this->actingAs($user)
            ->post(route('listen.episodes.playback.complete', $episode), [
                'position_seconds' => 899,
                'duration_seconds' => 900,
            ])
            ->assertOk()
            ->assertJsonPath('playback.status', EpisodePlayback::STATUS_COMPLETED)
            ->assertJsonPath('playback.last_position_seconds', 899)
            ->assertJsonPath('playback.duration_seconds', 900);

        $this->assertDatabaseHas('episode_playbacks', [
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_COMPLETED,
        ]);

        self::assertNotNull(EpisodePlayback::query()->whereBelongsTo($user)->whereBelongsTo($episode)->firstOrFail()->completed_at);
    }

    public function testCompletedStatusNeverChangesBackToInProgress(): void
    {
        $episode = Episode::factory()->create();
        $user = $this->allowedUser();

        EpisodePlayback::factory()->create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_COMPLETED,
            'completed_at' => now(),
            'play_count' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('listen.episodes.playback.start', $episode))
            ->assertOk()
            ->assertJsonPath('playback.status', EpisodePlayback::STATUS_COMPLETED)
            ->assertJsonPath('playback.play_count', 2);

        $this->actingAs($user)
            ->patch(route('listen.episodes.playback.progress', $episode), [
                'position_seconds' => 30,
                'duration_seconds' => 900,
            ])
            ->assertOk()
            ->assertJsonPath('playback.status', EpisodePlayback::STATUS_COMPLETED);

        $this->assertDatabaseHas('episode_playbacks', [
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_COMPLETED,
        ]);
    }

    public function testPlaybackStateIsPerUser(): void
    {
        $episode = Episode::factory()->create();
        $firstUser = User::factory()->create(['email' => 'first-' . Str::uuid() . '@example.test']);
        $secondUser = User::factory()->create(['email' => 'second-' . Str::uuid() . '@example.test']);
        config(['playpipe.admin.allowed_emails' => [$firstUser->email, $secondUser->email]]);

        $this->actingAs($firstUser)
            ->post(route('listen.episodes.playback.complete', $episode), [
                'position_seconds' => 900,
                'duration_seconds' => 900,
            ])
            ->assertOk();

        $this->actingAs($secondUser)
            ->post(route('listen.episodes.playback.start', $episode))
            ->assertOk()
            ->assertJsonPath('playback.status', EpisodePlayback::STATUS_IN_PROGRESS);

        $this->assertDatabaseHas('episode_playbacks', [
            'user_id' => $firstUser->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('episode_playbacks', [
            'user_id' => $secondUser->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_IN_PROGRESS,
        ]);
    }

    public function testGuestUsersCannotCallPlaybackTrackingRoutes(): void
    {
        $episode = Episode::factory()->create();

        $this->post(route('listen.episodes.playback.start', $episode))
            ->assertRedirect(route('auth.google.redirect'));

        $this->patch(route('listen.episodes.playback.progress', $episode), [
            'position_seconds' => 10,
        ])->assertRedirect(route('auth.google.redirect'));

        $this->post(route('listen.episodes.playback.complete', $episode))
            ->assertRedirect(route('auth.google.redirect'));
    }

    public function testInProgressEpisodeShowsResumeHintOnDetailPage(): void
    {
        $episode = Episode::factory()->create();
        $user = $this->allowedUser();
        EpisodePlayback::factory()->create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_IN_PROGRESS,
            'last_position_seconds' => 754,
            'duration_seconds' => 900,
        ]);

        $this->actingAs($user)
            ->get("/listen/episodes/{$episode->episode_key}")
            ->assertOk()
            ->assertSee('IN_PROGRESS')
            ->assertSee('RESUME 12:34')
            ->assertSee('data-resume-seconds="754"', false)
            ->assertSee(route('listen.episodes.playback.start', $episode), false)
            ->assertSee(route('listen.episodes.playback.progress', $episode), false)
            ->assertSee(route('listen.episodes.playback.complete', $episode), false);
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
