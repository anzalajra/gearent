<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use LaraZeus\Sky\Filament\Resources\PageResource\Pages\ListPage as ZeusListPage;

class ListPage extends ZeusListPage
{
    protected static string $resource = PageResource::class;
}
