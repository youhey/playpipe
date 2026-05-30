<?php

namespace App\Http\Controllers\Listen;

use App\Models\Episode;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class ListenEpisodeShowController extends Controller
{
    public function __invoke(Episode $episode): View
    {
        abort_unless($episode->status === Episode::STATUS_AVAILABLE, 404);

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

        return view('listen.episodes.show', [
            'episode' => $episode,
        ]);
    }
}
