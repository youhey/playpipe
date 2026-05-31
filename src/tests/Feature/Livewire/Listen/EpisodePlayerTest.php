<?php

namespace Tests\Feature\Livewire\Listen;

use App\Livewire\Listen\EpisodePlayer;
use App\Models\Episode;
use App\Models\EpisodePlayback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * @internal
 */
class EpisodePlayerTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthenticatedUserCanStartPlayback(): void
    {
        $episode = Episode::factory()->create(['audio_duration_seconds' => 900]);
        $user = $this->allowedUser();

        $component = $this->testComponent($user, $episode);
        $component->call('startPlayback', 10, 900);
        $component->assertSet('playbackStatus', EpisodePlayback::STATUS_IN_PROGRESS);

        $this->assertDatabaseHas('episode_playbacks', [
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_IN_PROGRESS,
            'last_position_seconds' => 10,
            'duration_seconds' => 900,
        ]);
    }

    public function testAuthenticatedUserCanSyncProgress(): void
    {
        $episode = Episode::factory()->create(['audio_duration_seconds' => 900]);
        $user = $this->allowedUser();

        $component = $this->testComponent($user, $episode);
        $component->call('syncProgress', 125, 900);
        $component->assertSet('playbackStatus', EpisodePlayback::STATUS_IN_PROGRESS);
        $component->assertSet('lastPositionSeconds', 125);
    }

    public function testAuthenticatedUserCanCompletePlayback(): void
    {
        $episode = Episode::factory()->create(['audio_duration_seconds' => 900]);
        $user = $this->allowedUser();

        $component = $this->testComponent($user, $episode);
        $component->call('completePlayback', 900, 900);
        $component->assertSet('playbackStatus', EpisodePlayback::STATUS_COMPLETED);
        $component->assertSet('resumeSeconds', 0);

        $this->assertDatabaseHas('episode_playbacks', [
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_COMPLETED,
        ]);
    }

    public function testGuestCannotCallPlaybackMethods(): void
    {
        $episode = Episode::factory()->create();
        $component = app(EpisodePlayer::class);
        $component->episode = $episode;

        $this->expectException(HttpException::class);

        $component->startPlayback(10, 900);
    }

    public function testCompletedRemainsTerminalThroughLivewireMethods(): void
    {
        $episode = Episode::factory()->create(['audio_duration_seconds' => 900]);
        $user = $this->allowedUser();
        EpisodePlayback::factory()->create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_COMPLETED,
            'completed_at' => now(),
            'last_position_seconds' => 900,
            'duration_seconds' => 900,
        ]);

        $component = $this->testComponent($user, $episode);
        $component->call('startPlayback', 10, 900);
        $component->call('syncProgress', 30, 900);
        $component->assertSet('playbackStatus', EpisodePlayback::STATUS_COMPLETED);
        $component->assertSet('resumeSeconds', 0);
    }

    public function testComponentExposesPlaybackStatusAndResumeSeconds(): void
    {
        $episode = Episode::factory()->create(['audio_duration_seconds' => 900]);
        $user = $this->allowedUser();
        EpisodePlayback::factory()->create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_IN_PROGRESS,
            'last_position_seconds' => 754,
            'duration_seconds' => 900,
        ]);

        $component = $this->testComponent($user, $episode);
        $component->assertSet('playbackStatus', EpisodePlayback::STATUS_IN_PROGRESS);
        $component->assertSet('resumeSeconds', 754);
        $component->assertSee('IN_PROGRESS');
        $component->assertSee('RESUME 12:34');
        $component->assertSee('data-listen-player', false);
        $component->assertSee('data-playback-status="in_progress"', false);
        $component->assertSee('data-resume-seconds="754"', false);
    }

    private function allowedUser(): User
    {
        $email = 'listener-' . Str::uuid() . '@example.test';
        config(['playpipe.admin.allowed_emails' => [$email]]);

        return User::factory()->create([
            'email' => $email,
        ]);
    }

    /**
     * @return Testable<EpisodePlayer>
     */
    private function testComponent(User $user, Episode $episode): Testable
    {
        Livewire::actingAs($user);

        return Livewire::test(EpisodePlayer::class, ['episode' => $episode]);
    }
}
