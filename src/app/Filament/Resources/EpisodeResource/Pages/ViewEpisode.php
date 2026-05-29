<?php

namespace App\Filament\Resources\EpisodeResource\Pages;

use App\Filament\Resources\EpisodeResource;
use App\Models\Episode;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use LogicException;

class ViewEpisode extends ViewRecord
{
    protected static string $resource = EpisodeResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        $episode = $this->episodeRecord();

        return [
            Action::make('playback')
                ->label('Open Playback')
                ->icon(Heroicon::OutlinedPlay)
                ->url(route('episodes.show', $episode))
                ->openUrlInNewTab(),
            Action::make('download')
                ->label('Download MP3')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->url(route('episodes.download', $episode))
                ->openUrlInNewTab(),
        ];
    }

    private function episodeRecord(): Episode
    {
        $record = $this->getRecord();

        if (! $record instanceof Episode) {
            throw new LogicException('Episode record is required.');
        }

        return $record;
    }
}
