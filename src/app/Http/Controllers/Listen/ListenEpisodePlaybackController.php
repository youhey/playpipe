<?php

namespace App\Http\Controllers\Listen;

use App\Models\Episode;
use App\Models\EpisodePlayback;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ListenEpisodePlaybackController extends Controller
{
    public function start(Request $request, Episode $episode): JsonResponse
    {
        abort_unless($episode->status === Episode::STATUS_AVAILABLE, 404);

        $playback = $this->playbackFor($request, $episode);
        $now = now();

        if (! $playback->exists) {
            $playback->status = EpisodePlayback::STATUS_IN_PROGRESS;
            $playback->first_played_at = $now;
            $playback->play_count = 0;
            $playback->last_position_seconds = 0;
        } elseif (! $playback->isCompleted()) {
            $playback->status = EpisodePlayback::STATUS_IN_PROGRESS;
        }

        if ($playback->first_played_at === null) {
            $playback->first_played_at = $now;
        }

        $playback->last_played_at = $now;
        $playback->duration_seconds ??= $episode->audio_duration_seconds;
        $playback->play_count = $playback->play_count + 1;
        $playback->save();

        return $this->playbackResponse($playback);
    }

    public function progress(Request $request, Episode $episode): JsonResponse
    {
        abort_unless($episode->status === Episode::STATUS_AVAILABLE, 404);

        /** @var array{position_seconds: int|float|string, duration_seconds?: int|float|string|null} $data */
        $data = Validator::make($request->all(), [
            'position_seconds' => ['required', 'numeric', 'min:0'],
            'duration_seconds' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        $durationSeconds = $this->normalizeSeconds($data['duration_seconds'] ?? null);
        $positionSeconds = $this->normalizeSeconds($data['position_seconds'], $durationSeconds) ?? 0;
        $playback = $this->playbackFor($request, $episode);
        $now = now();

        if (! $playback->exists) {
            $playback->status = EpisodePlayback::STATUS_IN_PROGRESS;
            $playback->first_played_at = $now;
            $playback->play_count = 1;
        } elseif (! $playback->isCompleted()) {
            $playback->status = EpisodePlayback::STATUS_IN_PROGRESS;
        }

        $playback->last_played_at = $now;
        $playback->last_position_seconds = $positionSeconds;
        $playback->duration_seconds = $this->normalizeSeconds(
            $durationSeconds ?? $playback->duration_seconds ?? $episode->audio_duration_seconds,
        );
        $playback->save();

        return $this->playbackResponse($playback);
    }

    public function complete(Request $request, Episode $episode): JsonResponse
    {
        abort_unless($episode->status === Episode::STATUS_AVAILABLE, 404);

        /** @var array{position_seconds?: int|float|string|null, duration_seconds?: int|float|string|null} $data */
        $data = Validator::make($request->all(), [
            'position_seconds' => ['nullable', 'numeric', 'min:0'],
            'duration_seconds' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        $durationSeconds = $this->normalizeSeconds($data['duration_seconds'] ?? null)
            ?? $episode->audio_duration_seconds;
        $positionSeconds = $this->normalizeSeconds($data['position_seconds'] ?? $durationSeconds, $durationSeconds) ?? 0;
        $playback = $this->playbackFor($request, $episode);
        $now = now();

        if (! $playback->exists) {
            $playback->first_played_at = $now;
            $playback->play_count = 1;
        }

        $playback->status = EpisodePlayback::STATUS_COMPLETED;
        $playback->last_played_at = $now;
        $playback->completed_at ??= $now;
        $playback->last_position_seconds = $positionSeconds;
        $playback->duration_seconds = $this->normalizeSeconds($durationSeconds ?? $playback->duration_seconds);
        $playback->save();

        return $this->playbackResponse($playback);
    }

    private function playbackFor(Request $request, Episode $episode): EpisodePlayback
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

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
            $durationSeconds = max(0, $durationSeconds);

            return min($seconds, max(0, $durationSeconds));
        }

        return $seconds;
    }

    private function playbackResponse(EpisodePlayback $playback): JsonResponse
    {
        return response()->json([
            'playback' => [
                'status' => $playback->status,
                'last_position_seconds' => $playback->last_position_seconds,
                'duration_seconds' => $playback->duration_seconds,
                'play_count' => $playback->play_count,
                'first_played_at' => $this->iso8601($playback->first_played_at),
                'last_played_at' => $this->iso8601($playback->last_played_at),
                'completed_at' => $this->iso8601($playback->completed_at),
            ],
        ]);
    }

    private function iso8601(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if (is_string($value) && $value !== '') {
            return CarbonImmutable::parse($value)->toIso8601String();
        }

        return null;
    }
}
