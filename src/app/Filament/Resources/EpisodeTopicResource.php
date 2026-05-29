<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EpisodeTopicResource\Pages\ListEpisodeTopics;
use App\Models\EpisodeTopic;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class EpisodeTopicResource extends Resource
{
    protected static ?string $model = EpisodeTopic::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Episode Topics';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('episode.episode_key')
                    ->label('Episode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('topic_id')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('source_name')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('url')
                    ->limit(48)
                    ->placeholder('N/A'),
            ])
            ->recordActions([
                Action::make('source')
                    ->label('Source')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(static fn (EpisodeTopic $record): ?string => $record->url)
                    ->openUrlInNewTab()
                    ->visible(static fn (EpisodeTopic $record): bool => is_string($record->url) && $record->url !== ''),
            ]);
    }

    /**
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListEpisodeTopics::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
