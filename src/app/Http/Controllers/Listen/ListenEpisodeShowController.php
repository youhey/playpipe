<?php

namespace App\Http\Controllers\Listen;

use App\Models\Episode;
use App\Models\EpisodePlayback;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ListenEpisodeShowController extends Controller
{
    public function __invoke(Request $request, Episode $episode): View
    {
        abort_unless($episode->status === Episode::STATUS_AVAILABLE, 404);

        /** @var User $user */
        $user = $request->user();

        $sectionQuery = $episode->sections()->getQuery();
        $sectionQuery->getQuery()
            ->orderBy('sort_order')
            ->orderBy('id');

        $topicQuery = $episode->topics()->getQuery();
        $topicQuery->getQuery()
            ->orderBy('sort_order')
            ->orderBy('id');

        $episode->setRelation('sections', $sectionQuery->get());
        $episode->setRelation('topics', $topicQuery->get());
        $episode->setRelation('playbacks', EpisodePlayback::query()
            ->where('user_id', $user->id)
            ->where('episode_id', $episode->id)
            ->get());

        return view('listen.episodes.show', [
            'episode' => $episode,
        ]);
    }
}
