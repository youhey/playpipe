<?php

namespace App\Http\Controllers\Listen;

use App\Models\Episode;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ListenEpisodeIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var array{q?: string, character?: string} $filters */
        $filters = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:255'],
            'character' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $search = $filters['q'] ?? null;
        $character = $filters['character'] ?? null;

        $query = Episode::query()
            ->withCount('topics')
            ->where('status', Episode::STATUS_AVAILABLE);

        if (is_string($search)) {
            $term = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';

            $query->where(static function (Builder $query) use ($term): void {
                $query->where('title', 'like', $term)
                    ->orWhere('episode_key', 'like', $term);
            });
        }

        if (is_string($character)) {
            $query->where('character_key', $character);
        }

        $query->getQuery()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        $episodes = $query
            ->paginate(12)
            ->withQueryString();

        $characters = DB::table('episodes')
            ->select(['character_key', 'character_name'])
            ->where('status', Episode::STATUS_AVAILABLE)
            ->whereNotNull('character_key')
            ->orderBy('character_name')
            ->orderBy('character_key')
            ->get()
            ->unique('character_key')
            ->values();

        return view('listen.episodes.index', [
            'episodes' => $episodes,
            'filters' => $filters,
            'characters' => $characters,
        ]);
    }
}
