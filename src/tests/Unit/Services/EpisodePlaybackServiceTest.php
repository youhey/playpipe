<?php

namespace Tests\Unit\Services;

use App\Models\Episode;
use App\Models\EpisodePlayback;
use App\Models\User;
use App\Services\Episodes\EpisodePlaybackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 */
class EpisodePlaybackServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testUnplayedIsRepresentedByMissingRecord(): void
    {
        $service = app(EpisodePlaybackService::class);
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        self::assertSame(EpisodePlaybackService::STATUS_UNPLAYED, $service->statusFor($user, $episode));
        self::assertNull($service->existingPlaybackFor($user, $episode));
    }

    public function testStartCreatesInProgressRecord(): void
    {
        $service = app(EpisodePlaybackService::class);
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        $playback = $service->start($user, $episode, 12, 900);

        self::assertSame(EpisodePlayback::STATUS_IN_PROGRESS, $playback->status);
        self::assertSame(12, $playback->last_position_seconds);
        self::assertSame(900, $playback->duration_seconds);
        self::assertSame(1, $playback->play_count);
        self::assertNotNull($playback->first_played_at);
        self::assertNotNull($playback->last_played_at);
    }

    public function testStartMultipleTimesDoesNotCreateDuplicateRecord(): void
    {
        $service = app(EpisodePlaybackService::class);
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        $service->start($user, $episode, null, null);
        $playback = $service->start($user, $episode, null, null);

        self::assertSame(EpisodePlayback::STATUS_IN_PROGRESS, $playback->status);
        self::assertSame(2, $playback->play_count);
        $this->assertDatabaseCount('episode_playbacks', 1);
    }

    public function testCompleteChangesStatusToCompleted(): void
    {
        $service = app(EpisodePlaybackService::class);
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        $playback = $service->complete($user, $episode, 899, 900);

        self::assertSame(EpisodePlayback::STATUS_COMPLETED, $playback->status);
        self::assertSame(899, $playback->last_position_seconds);
        self::assertSame(900, $playback->duration_seconds);
        self::assertNotNull($playback->completed_at);
    }

    public function testCompletedStatusDoesNotReturnToInProgressOnStart(): void
    {
        $service = app(EpisodePlaybackService::class);
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        $service->complete($user, $episode, 900, 900);
        $playback = $service->start($user, $episode, 10, 900);

        self::assertSame(EpisodePlayback::STATUS_COMPLETED, $playback->status);
        self::assertSame(2, $playback->play_count);
    }

    public function testCompletedStatusDoesNotReturnToInProgressOnProgress(): void
    {
        $service = app(EpisodePlaybackService::class);
        $user = User::factory()->create();
        $episode = Episode::factory()->create();

        $service->complete($user, $episode, 900, 900);
        $playback = $service->syncProgress($user, $episode, 30, 900);

        self::assertSame(EpisodePlayback::STATUS_COMPLETED, $playback->status);
    }

    public function testResumeSecondsReturnsInProgressPosition(): void
    {
        $service = app(EpisodePlaybackService::class);
        $user = User::factory()->create();
        $episode = Episode::factory()->create();
        EpisodePlayback::factory()->create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_IN_PROGRESS,
            'last_position_seconds' => 754,
            'duration_seconds' => 900,
        ]);

        self::assertSame(754, $service->resumeSecondsFor($user, $episode));
    }

    public function testResumeSecondsReturnsZeroForCompleted(): void
    {
        $service = app(EpisodePlaybackService::class);
        $user = User::factory()->create();
        $episode = Episode::factory()->create();
        EpisodePlayback::factory()->create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_COMPLETED,
            'last_position_seconds' => 754,
            'duration_seconds' => 900,
            'completed_at' => now(),
        ]);

        self::assertSame(0, $service->resumeSecondsFor($user, $episode));
    }

    public function testResumeSecondsReturnsZeroForShortPosition(): void
    {
        $service = app(EpisodePlaybackService::class);
        $user = User::factory()->create();
        $episode = Episode::factory()->create();
        EpisodePlayback::factory()->create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_IN_PROGRESS,
            'last_position_seconds' => 4,
            'duration_seconds' => 900,
        ]);

        self::assertSame(0, $service->resumeSecondsFor($user, $episode));
    }

    public function testResumeSecondsReturnsZeroNearEnd(): void
    {
        $service = app(EpisodePlaybackService::class);
        $user = User::factory()->create();
        $episode = Episode::factory()->create();
        EpisodePlayback::factory()->create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'status' => EpisodePlayback::STATUS_IN_PROGRESS,
            'last_position_seconds' => 891,
            'duration_seconds' => 900,
        ]);

        self::assertSame(0, $service->resumeSecondsFor($user, $episode));
    }

    public function testPlaybackStateIsIndependentPerUser(): void
    {
        $service = app(EpisodePlaybackService::class);
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $episode = Episode::factory()->create();

        $service->complete($firstUser, $episode, 900, 900);
        $service->start($secondUser, $episode, 10, 900);

        self::assertSame(EpisodePlayback::STATUS_COMPLETED, $service->statusFor($firstUser, $episode));
        self::assertSame(EpisodePlayback::STATUS_IN_PROGRESS, $service->statusFor($secondUser, $episode));
    }
}
