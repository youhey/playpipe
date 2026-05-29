<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EpisodeResource\Pages\ListEpisodes;
use App\Filament\Resources\EpisodeResource\Pages\ViewEpisode;
use App\Models\Episode;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * 取り込み済み Episode を確認する read-only Resource。
 */
class EpisodeResource extends Resource
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s T';

    protected static ?string $model = Episode::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedRadio;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Episodes';

    protected static ?string $modelLabel = 'Episode';

    protected static ?string $pluralModelLabel = 'Episodes';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('episode_key')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('language')
                    ->sortable(),
                TextColumn::make('character_name')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime(self::DATETIME_FORMAT)
                    ->placeholder('N/A')
                    ->sortable(),
                TextColumn::make('recorded_at')
                    ->dateTime(self::DATETIME_FORMAT)
                    ->placeholder('N/A')
                    ->sortable(),
                TextColumn::make('audio_duration_seconds')
                    ->numeric()
                    ->placeholder('N/A')
                    ->sortable(),
                TextColumn::make('audio_size_bytes')
                    ->numeric()
                    ->placeholder('N/A')
                    ->sortable(),
                TextColumn::make('topics_count')
                    ->counts('topics')
                    ->label('Topics')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime(self::DATETIME_FORMAT)
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('playback')
                    ->label('Open Playback')
                    ->icon(Heroicon::OutlinedPlay)
                    ->url(static fn (Episode $record): string => route('episodes.show', $record))
                    ->openUrlInNewTab(),
                Action::make('download')
                    ->label('Download MP3')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(static fn (Episode $record): string => route('episodes.download', $record))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Episode')
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('episode_key'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('language'),
                        TextEntry::make('character_name')->placeholder('N/A'),
                        TextEntry::make('published_at')->dateTime(self::DATETIME_FORMAT)->placeholder('N/A'),
                        TextEntry::make('recorded_at')->dateTime(self::DATETIME_FORMAT)->placeholder('N/A'),
                        TextEntry::make('audio_duration_seconds')->numeric()->placeholder('N/A'),
                        TextEntry::make('audio_size_bytes')->numeric()->placeholder('N/A'),
                        TextEntry::make('voicepipe_version')->placeholder('N/A'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Scenario')
                    ->schema([
                        TextEntry::make('sections_summary')
                            ->label('Sections')
                            ->state(static fn (Episode $record): string => self::sectionsSummary($record))
                            ->columnSpanFull(),
                        TextEntry::make('topics_summary')
                            ->label('Topics')
                            ->state(static fn (Episode $record): string => self::topicsSummary($record))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListEpisodes::route('/'),
            'view' => ViewEpisode::route('/{record}'),
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

    private static function sectionsSummary(Episode $episode): string
    {
        $episode->loadMissing('sections');

        return $episode->sections
            ->sortBy('sort_order')
            ->map(static fn ($section): string => "#{$section->sort_order} {$section->section_type}: {$section->title}")
            ->implode("\n");
    }

    private static function topicsSummary(Episode $episode): string
    {
        $episode->loadMissing('topics');

        return $episode->topics
            ->sortBy('sort_order')
            ->map(static fn ($topic): string => "#{$topic->sort_order} {$topic->title}")
            ->implode("\n");
    }
}
