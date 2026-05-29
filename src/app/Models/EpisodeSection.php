<?php

namespace App\Models;

use Database\Factories\EpisodeSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Episode scenario_json.sections から展開した section。
 */
#[Fillable([
    'episode_id',
    'section_type',
    'title',
    'text',
    'estimated_duration_seconds',
    'sort_order',
    'raw_section_json',
])]
class EpisodeSection extends Model
{
    /** @use HasFactory<EpisodeSectionFactory> */
    use HasFactory;

    /**
     * 所属する Episode。
     *
     * @return BelongsTo<Episode, $this>
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_duration_seconds' => 'integer',
            'sort_order' => 'integer',
            'raw_section_json' => 'array',
        ];
    }
}
