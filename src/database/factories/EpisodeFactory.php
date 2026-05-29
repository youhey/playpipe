<?php

namespace Database\Factories;

use App\Models\Episode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Episode>
 */
class EpisodeFactory extends Factory
{
    protected $model = Episode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $episodeKey = 'episode-' . $this->faker->unique()->slug(3);

        return [
            'episode_key' => $episodeKey,
            'status' => Episode::STATUS_AVAILABLE,
            'title' => 'サンプルエピソード',
            'language' => 'ja',
            'character_key' => 'neko_nyan_balanced_radio',
            'character_name' => 'ねこにゃん',
            'published_at' => now()->subHour(),
            'processed_at' => now()->subMinutes(55),
            'recorded_at' => now()->subMinutes(50),
            'audio_disk' => 's3',
            'audio_path' => "episodes/{$episodeKey}/audio.mp3",
            'audio_size_bytes' => 128000,
            'audio_duration_seconds' => 900,
            'voicepipe_version' => 'voicepipe-test',
            'episode_json' => [
                'episode' => [
                    'episode_key' => $episodeKey,
                    'title' => 'サンプルエピソード',
                    'language' => 'ja',
                    'scenario_json' => [
                        'sections' => [
                            [
                                'type' => 'opening',
                                'title' => 'オープニング',
                                'text' => 'こんにちは。',
                            ],
                        ],
                    ],
                ],
            ],
            'scenario_json' => [
                'sections' => [
                    [
                        'type' => 'opening',
                        'title' => 'オープニング',
                        'text' => 'こんにちは。',
                    ],
                ],
            ],
            'render_metadata_json' => ['speaker' => 'test'],
        ];
    }
}
