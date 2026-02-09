<?php

namespace App\Filament\Resources\TagResource\Pages;

use LaraZeus\Sky\Filament\Resources\TagResource\Pages\ListTags as BaseListTags;
use App\Filament\Resources\TagResource;

class ListTags extends BaseListTags
{
    protected static string $resource = TagResource::class;
}
