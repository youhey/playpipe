<?php

namespace App\Models;

use Database\Factories\EpisodePlaybackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'episode_id',
    'status',
    'first_played_at',
    'last_played_at',
    'completed_at',
    'last_position_seconds',
    'duration_seconds',
    'play_count',
])]
class EpisodePlayback extends Model
{
    /** @use HasFactory<EpisodePlaybackFactory> */
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Episode, $this>
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_played_at' => 'datetime',
            'last_played_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_position_seconds' => 'integer',
            'duration_seconds' => 'integer',
            'play_count' => 'integer',
        ];
    }
}
