<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use LaraZeus\Sky\Filament\Resources\PageResource\Pages\CreatePage as ZeusCreatePage;

class CreatePage extends ZeusCreatePage
{
    protected static string $resource = PageResource::class;
}
