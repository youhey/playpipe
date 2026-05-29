<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;

/**
 * User metadata 一覧ページ。
 */
class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
