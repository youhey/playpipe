<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\EpisodePlayback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EpisodePlayback>
 */
class EpisodePlaybackFactory extends Factory
{
    protected $model = EpisodePlayback::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'episode_id' => Episode::factory(),
            'status' => EpisodePlayback::STATUS_IN_PROGRESS,
            'first_played_at' => now()->subMinutes(10),
            'last_played_at' => now(),
            'completed_at' => null,
            'last_position_seconds' => 120,
            'duration_seconds' => 900,
            'play_count' => 1,
        ];
    }
}
