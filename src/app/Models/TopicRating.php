<?php

namespace App\Models;

use Database\Factories\TopicRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'topic_id',
    'latest_episode_topic_id',
    'rating',
    'rated_at',
    'synced_at',
    'last_sync_error',
])]
class TopicRating extends Model
{
    /** @use HasFactory<TopicRatingFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<EpisodeTopic, $this>
     */
    public function latestEpisodeTopic(): BelongsTo
    {
        return $this->belongsTo(EpisodeTopic::class, 'latest_episode_topic_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'rated_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
