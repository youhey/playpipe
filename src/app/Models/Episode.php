<?php

namespace App\Models;

use Database\Factories\EpisodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * playpipe に取り込んだ再生用 Episode。
 */
#[Fillable([
    'episode_key',
    'status',
    'title',
    'language',
    'character_key',
    'character_name',
    'published_at',
    'processed_at',
    'recorded_at',
    'audio_disk',
    'audio_path',
    'audio_size_bytes',
    'audio_duration_seconds',
    'voicepipe_version',
    'episode_json',
    'scenario_json',
    'render_metadata_json',
])]
class Episode extends Model
{
    /** @use HasFactory<EpisodeFactory> */
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';

    /**
     * Playback URL では DB id ではなく episode_key を使う。
     */
    public function getRouteKeyName(): string
    {
        return 'episode_key';
    }

    /**
     * Episode の scenario section 一覧。
     *
     * @return HasMany<EpisodeSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(EpisodeSection::class);
    }

    /**
     * Episode に含まれる topic snapshot 一覧。
     *
     * @return HasMany<EpisodeTopic, $this>
     */
    public function topics(): HasMany
    {
        return $this->hasMany(EpisodeTopic::class);
    }

    /**
     * Episode に対する user playback state 一覧。
     *
     * @return HasMany<EpisodePlayback, $this>
     */
    public function playbacks(): HasMany
    {
        return $this->hasMany(EpisodePlayback::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'processed_at' => 'datetime',
            'recorded_at' => 'datetime',
            'audio_size_bytes' => 'integer',
            'audio_duration_seconds' => 'integer',
            'episode_json' => 'array',
            'scenario_json' => 'array',
            'render_metadata_json' => 'array',
        ];
    }
}
