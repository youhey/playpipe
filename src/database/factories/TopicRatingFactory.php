<?php

namespace Database\Factories;

use App\Models\EpisodeTopic;
use App\Models\TopicRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TopicRating>
 */
class TopicRatingFactory extends Factory
{
    protected $model = TopicRating::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $topicId = 'upstream:' . $this->faker->unique()->numberBetween(100, 999);

        return [
            'user_id' => User::factory(),
            'topic_id' => $topicId,
            'latest_episode_topic_id' => EpisodeTopic::factory(['topic_id' => $topicId]),
            'rating' => 1,
            'rated_at' => now(),
            'synced_at' => now(),
            'last_sync_error' => null,
        ];
    }
}
