<?php

namespace App\Services\Listen;

use App\Exceptions\RadiopipeTopicRatingException;
use App\Models\Episode;
use App\Models\EpisodeTopic;
use App\Models\TopicRating;
use App\Models\User;
use App\Services\Radiopipe\RadiopipeTopicRatingClient;
use Carbon\CarbonImmutable;

class TopicRatingService
{
    public function __construct(private readonly RadiopipeTopicRatingClient $client)
    {
    }

    public function rate(User $user, EpisodeTopic $topic, int $rating): TopicRating
    {
        $topicId = $this->ratingTopicId($topic);
        $this->validateRating($rating);
        $response = $this->client->rate($topicId, $rating);

        return TopicRating::query()->updateOrCreate([
            'user_id' => $user->id,
            'topic_id' => $topicId,
        ], [
            'latest_episode_topic_id' => $topic->id,
            'rating' => $rating,
            'rated_at' => $this->ratedAt($response) ?? now(),
            'synced_at' => now(),
            'last_sync_error' => null,
        ]);
    }

    public function clear(User $user, EpisodeTopic $topic): void
    {
        $topicId = $this->ratingTopicId($topic);
        $this->client->clear($topicId);

        TopicRating::query()
            ->where('user_id', $user->id)
            ->where('topic_id', $topicId)
            ->delete();
    }

    public function currentRatingFor(User $user, EpisodeTopic $topic): ?TopicRating
    {
        $topicId = $this->topicId($topic);

        if ($topicId === null) {
            return null;
        }

        return TopicRating::query()
            ->where('user_id', $user->id)
            ->where('topic_id', $topicId)
            ->first();
    }

    public function isRateable(EpisodeTopic $topic): bool
    {
        return $this->topicId($topic) !== null
            && $topic->episode?->status === Episode::STATUS_AVAILABLE;
    }

    private function ratingTopicId(EpisodeTopic $topic): string
    {
        $topicId = $this->topicId($topic);

        if ($topicId === null) {
            throw new RadiopipeTopicRatingException('Topic id is required for rating sync.');
        }

        $topic->loadMissing('episode');

        if ($topic->episode?->status !== Episode::STATUS_AVAILABLE) {
            throw new RadiopipeTopicRatingException('Topic is not available for rating sync.');
        }

        return $topicId;
    }

    private function topicId(EpisodeTopic $topic): ?string
    {
        $topicId = $topic->topic_id;

        if (! is_string($topicId) || trim($topicId) === '') {
            return null;
        }

        return trim($topicId);
    }

    private function validateRating(int $rating): void
    {
        if (! in_array($rating, [-1, 1, 2, 3, 4, 5], true)) {
            throw new RadiopipeTopicRatingException('Topic rating must be -1 or 1..5.');
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    private function ratedAt(array $response): ?CarbonImmutable
    {
        $ratedAt = data_get($response, 'topic_rating.rated_at');

        if (! is_string($ratedAt) || trim($ratedAt) === '') {
            return null;
        }

        return CarbonImmutable::parse($ratedAt);
    }
}
