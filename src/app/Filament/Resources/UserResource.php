<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * 管理画面で User metadata を確認するための read-only Resource。
 */
class UserResource extends Resource
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';

    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    /**
     * 一覧テーブルを構成する。
     */
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('google_id')
                    ->label('Google ID')
                    ->placeholder('N/A')
                    ->toggleable(),
                TextColumn::make('email_verified_at')
                    ->dateTime(self::DATETIME_FORMAT)
                    ->placeholder('N/A')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime(self::DATETIME_FORMAT)
                    ->sortable(),
            ]);
    }

    /**
     * Resource page route を返す。
     *
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }

    /**
     * User は OAuth / API user command で作成する。
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * User metadata は Phase 1 の管理画面から編集しない。
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * User の削除は Phase 1 の管理画面から許可しない。
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * bulk delete を許可しない。
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }
}
