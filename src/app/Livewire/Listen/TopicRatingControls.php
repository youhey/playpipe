<?php

namespace App\Livewire\Listen;

use App\Admin\AdminAccess;
use App\Exceptions\RadiopipeTopicRatingException;
use App\Models\Episode;
use App\Models\EpisodeTopic;
use App\Models\User;
use App\Services\Listen\TopicRatingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TopicRatingControls extends Component
{
    public EpisodeTopic $topic;

    public ?int $rating = null;

    public ?string $errorMessage = null;

    public bool $rateable = false;

    public function mount(EpisodeTopic $topic): void
    {
        $this->topic = $topic;
        $this->refreshRatingState();
    }

    public function rate(int $rating): void
    {
        abort_unless(in_array($rating, [-1, 1, 2, 3, 4, 5], true), 422);

        $user = $this->authorizedUser();
        $service = app(TopicRatingService::class);
        $this->ensureTopicCanBeRated($service);

        if ($this->rating === $rating) {
            $this->clear();

            return;
        }

        try {
            $service->rate($user, $this->topic, $rating);
            $this->errorMessage = null;
            $this->refreshRatingState();
        } catch (RadiopipeTopicRatingException) {
            $this->errorMessage = 'SYNC_FAILED';
        }
    }

    public function clear(): void
    {
        $user = $this->authorizedUser();
        $service = app(TopicRatingService::class);
        $this->ensureTopicCanBeRated($service);

        try {
            $service->clear($user, $this->topic);
            $this->errorMessage = null;
            $this->refreshRatingState();
        } catch (RadiopipeTopicRatingException) {
            $this->errorMessage = 'SYNC_FAILED';
        }
    }

    public function render(): View
    {
        return view('livewire.listen.topic-rating-controls');
    }

    private function authorizedUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        abort_unless(app(AdminAccess::class)->isAllowedEmail($user->email), 403);

        $this->topic->loadMissing('episode');
        abort_unless($this->topic->episode?->status === Episode::STATUS_AVAILABLE, 404);

        return $user;
    }

    private function ensureTopicCanBeRated(TopicRatingService $service): void
    {
        abort_unless($service->isRateable($this->topic), 422);
    }

    private function refreshRatingState(): void
    {
        $service = app(TopicRatingService::class);
        $this->topic->loadMissing('episode');
        $this->rateable = $service->isRateable($this->topic);

        $user = Auth::user();

        if (! $user instanceof User || ! app(AdminAccess::class)->isAllowedEmail($user->email)) {
            $this->rating = null;

            return;
        }

        $this->rating = $service->currentRatingFor($user, $this->topic)?->rating;
    }
}
