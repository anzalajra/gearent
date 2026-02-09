<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use LaraZeus\Sky\Filament\Resources\PageResource\Pages\EditPage as ZeusEditPage;

class EditPage extends ZeusEditPage
{
    protected static string $resource = PageResource::class;
}
