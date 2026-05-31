<?php

namespace App\Services\Episodes;

use App\Models\Episode;
use App\Models\EpisodePlayback;
use App\Models\User;

class EpisodePlaybackService
{
    public const STATUS_UNPLAYED = 'unplayed';

    public function start(User $user, Episode $episode, ?int $positionSeconds, ?int $durationSeconds): EpisodePlayback
    {
        $durationSeconds = $this->normalizeSeconds($durationSeconds ?? $episode->audio_duration_seconds);
        $positionSeconds = $this->normalizeSeconds($positionSeconds, $durationSeconds);
        $playback = $this->playbackFor($user, $episode);
        $now = now();

        if (! $playback->exists) {
            $playback->status = EpisodePlayback::STATUS_IN_PROGRESS;
            $playback->first_played_at = $now;
            $playback->play_count = 0;
            $playback->last_position_seconds = $positionSeconds ?? 0;
        } elseif (! $playback->isCompleted()) {
            $playback->status = EpisodePlayback::STATUS_IN_PROGRESS;
            $playback->last_position_seconds = $positionSeconds ?? $playback->last_position_seconds;
        }

        $playback->first_played_at ??= $now;
        $playback->last_played_at = $now;
        $playback->duration_seconds = $durationSeconds ?? $playback->duration_seconds;
        $playback->play_count = $playback->play_count + 1;
        $playback->save();

        return $playback;
    }

    public function syncProgress(User $user, Episode $episode, int $positionSeconds, ?int $durationSeconds): EpisodePlayback
    {
        $durationSeconds = $this->normalizeSeconds($durationSeconds);
        $positionSeconds = $this->normalizeSeconds($positionSeconds, $durationSeconds) ?? 0;
        $playback = $this->playbackFor($user, $episode);
        $now = now();

        if (! $playback->exists) {
            $playback->status = EpisodePlayback::STATUS_IN_PROGRESS;
            $playback->first_played_at = $now;
            $playback->play_count = 1;
        } elseif (! $playback->isCompleted()) {
            $playback->status = EpisodePlayback::STATUS_IN_PROGRESS;
        }

        $playback->last_played_at = $now;
        $playback->duration_seconds = $durationSeconds ?? $playback->duration_seconds ?? $episode->audio_duration_seconds;

        if (! $playback->isCompleted()) {
            $playback->last_position_seconds = $this->normalizeSeconds($positionSeconds, $playback->duration_seconds) ?? 0;
        }

        $playback->save();

        return $playback;
    }

    public function complete(User $user, Episode $episode, ?int $positionSeconds, ?int $durationSeconds): EpisodePlayback
    {
        $durationSeconds = $this->normalizeSeconds($durationSeconds ?? $episode->audio_duration_seconds);
        $positionSeconds = $this->normalizeSeconds($positionSeconds ?? $durationSeconds, $durationSeconds) ?? 0;
        $playback = $this->playbackFor($user, $episode);
        $now = now();

        if (! $playback->exists) {
            $playback->first_played_at = $now;
            $playback->play_count = 1;
        }

        $playback->status = EpisodePlayback::STATUS_COMPLETED;
        $playback->last_played_at = $now;
        $playback->completed_at ??= $now;
        $playback->last_position_seconds = $positionSeconds;
        $playback->duration_seconds = $durationSeconds ?? $playback->duration_seconds;
        $playback->save();

        return $playback;
    }

    public function statusFor(User $user, Episode $episode): string
    {
        $playback = $this->existingPlaybackFor($user, $episode);

        return $playback === null ? self::STATUS_UNPLAYED : $playback->status;
    }

    public function resumeSecondsFor(User $user, Episode $episode): int
    {
        $playback = $this->existingPlaybackFor($user, $episode);

        if ($playback === null || $playback->status !== EpisodePlayback::STATUS_IN_PROGRESS) {
            return 0;
        }

        $positionSeconds = max(0, $playback->last_position_seconds);

        if ($positionSeconds < 5) {
            return 0;
        }

        $durationSeconds = $this->normalizeSeconds($playback->duration_seconds ?? $episode->audio_duration_seconds);

        if ($durationSeconds !== null && $positionSeconds >= max(0, $durationSeconds - 10)) {
            return 0;
        }

        return $positionSeconds;
    }

    public function existingPlaybackFor(User $user, Episode $episode): ?EpisodePlayback
    {
        return EpisodePlayback::query()
            ->where('user_id', $user->id)
            ->where('episode_id', $episode->id)
            ->first();
    }

    private function playbackFor(User $user, Episode $episode): EpisodePlayback
    {
        return EpisodePlayback::query()->firstOrNew([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
        ]);
    }

    /**
     * @return int<0, max>|null
     */
    private function normalizeSeconds(mixed $value, ?int $durationSeconds = null): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        /** @var int<0, max> $seconds */
        $seconds = max(0, (int) floor((float) $value));

        if ($durationSeconds !== null) {
            /** @var int<0, max> $durationSeconds */
            $durationSeconds = max(0, $durationSeconds);

            return min($seconds, $durationSeconds);
        }

        return $seconds;
    }
}
