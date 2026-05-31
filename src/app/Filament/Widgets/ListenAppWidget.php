<?php

namespace App\Filament\Widgets;

use App\Models\Episode;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ListenAppWidget extends Widget
{
    protected string $view = 'filament.widgets.listen-app';

    protected array|int|string $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $latestEpisodeQuery = Episode::query()
            ->where('status', Episode::STATUS_AVAILABLE);
        $latestEpisodeQuery->getQuery()
            ->orderByDesc('published_at')
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at');

        $latestEpisode = $latestEpisodeQuery->first();
        $latestEpisodeDate = $latestEpisode instanceof Episode
            ? ($latestEpisode->published_at ?? $latestEpisode->recorded_at ?? $latestEpisode->created_at)
            : null;
        $storageDisk = config('playpipe.upload.storage_disk', config('filesystems.default', 's3'));

        return [
            'latestEpisode' => $latestEpisode,
            'availableEpisodeCount' => DB::table('episodes')
                ->where('status', Episode::STATUS_AVAILABLE)
                ->count(),
            'latestEpisodeDate' => $latestEpisodeDate,
            'storageDisk' => is_string($storageDisk) ? $storageDisk : 's3',
        ];
    }
}
