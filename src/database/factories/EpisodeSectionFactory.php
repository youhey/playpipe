<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\EpisodeSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EpisodeSection>
 */
class EpisodeSectionFactory extends Factory
{
    protected $model = EpisodeSection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'episode_id' => Episode::factory(),
            'section_type' => 'topic',
            'title' => '本文',
            'text' => "今日のトピックを紹介します。\n詳しく見ていきましょう。",
            'estimated_duration_seconds' => 60,
            'sort_order' => 1,
            'raw_section_json' => [
                'type' => 'topic',
                'title' => '本文',
                'text' => '今日のトピックを紹介します。',
                'estimated_duration_seconds' => 60,
            ],
        ];
    }
}
