<?php

namespace App\Livewire\Listen;

use App\Admin\AdminAccess;
use App\Models\Episode;
use App\Models\EpisodePlayback;
use App\Models\User;
use App\Services\Episodes\EpisodePlaybackService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EpisodePlayer extends Component
{
    public Episode $episode;

    public string $playbackStatus = EpisodePlaybackService::STATUS_UNPLAYED;

    public int $resumeSeconds = 0;

    public ?int $lastPositionSeconds = null;

    public ?int $durationSeconds = null;

    public function mount(Episode $episode): void
    {
        $this->episode = $episode;
        $this->refreshPlaybackState();
    }

    public function startPlayback(?int $positionSeconds = null, ?int $durationSeconds = null): void
    {
        $user = $this->authorizedUser();
        $playbackService = app(EpisodePlaybackService::class);

        $playbackService->start($user, $this->episode, $positionSeconds, $durationSeconds);
        $this->refreshPlaybackState();
    }

    public function syncProgress(int $positionSeconds, ?int $durationSeconds = null): void
    {
        $user = $this->authorizedUser();
        $playbackService = app(EpisodePlaybackService::class);

        $playbackService->syncProgress($user, $this->episode, $positionSeconds, $durationSeconds);
        $this->refreshPlaybackState();
    }

    public function completePlayback(?int $positionSeconds = null, ?int $durationSeconds = null): void
    {
        $user = $this->authorizedUser();
        $playbackService = app(EpisodePlaybackService::class);

        $playbackService->complete($user, $this->episode, $positionSeconds, $durationSeconds);
        $this->refreshPlaybackState();
    }

    public function render(): View
    {
        return view('livewire.listen.episode-player');
    }

    public function playbackLabel(): string
    {
        return match ($this->playbackStatus) {
            EpisodePlayback::STATUS_COMPLETED => 'COMPLETED',
            EpisodePlayback::STATUS_IN_PROGRESS => 'IN_PROGRESS',
            default => 'UNPLAYED',
        };
    }

    private function authorizedUser(): User
    {
        abort_unless($this->episode->status === Episode::STATUS_AVAILABLE, 404);

        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        abort_unless(app(AdminAccess::class)->isAllowedEmail($user->email), 403);

        return $user;
    }

    private function refreshPlaybackState(): void
    {
        $user = $this->authorizedUser();
        $playbackService = app(EpisodePlaybackService::class);
        $playback = $playbackService->existingPlaybackFor($user, $this->episode);

        $this->resumeSeconds = $playbackService->resumeSecondsFor($user, $this->episode);

        if ($playback === null) {
            $this->playbackStatus = EpisodePlaybackService::STATUS_UNPLAYED;
            $this->lastPositionSeconds = null;
            $this->durationSeconds = $this->episode->audio_duration_seconds;

            return;
        }

        $this->playbackStatus = $playback->status;
        $this->lastPositionSeconds = $playback->last_position_seconds;
        $this->durationSeconds = $playback->duration_seconds ?? $this->episode->audio_duration_seconds;
    }
}
