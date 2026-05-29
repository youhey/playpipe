<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\EpisodeTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EpisodeTopic>
 */
class EpisodeTopicFactory extends Factory
{
    protected $model = EpisodeTopic::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'episode_id' => Episode::factory(),
            'topic_id' => 'upstream:' . $this->faker->unique()->numberBetween(100, 999),
            'status' => 'used_in_scenario',
            'title' => 'GitHub アカウントのセキュリティ設定を点検する CLI',
            'summary' => 'GitHub の設定を読み取り専用で点検する CLI ツールです。',
            'why_it_matters' => 'リポジトリ運用やサプライチェーン安全性に関わります。',
            'source_name' => 'Laravel News',
            'url' => 'https://laravel-news.com/example',
            'discussion_url' => 'https://news.ycombinator.com/item?id=236',
            'sort_order' => 1,
            'raw_topic_json' => [
                'topic_id' => 'upstream:236',
                'status' => 'used_in_scenario',
            ],
        ];
    }
}
