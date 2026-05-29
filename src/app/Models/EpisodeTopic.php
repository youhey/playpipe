<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Episode JSON の topics から展開した topic snapshot。
 */
#[Fillable([
    'episode_id',
    'topic_id',
    'status',
    'title',
    'summary',
    'why_it_matters',
    'source_name',
    'url',
    'discussion_url',
    'sort_order',
    'raw_topic_json',
])]
class EpisodeTopic extends Model
{
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
            'sort_order' => 'integer',
            'raw_topic_json' => 'array',
        ];
    }
}
