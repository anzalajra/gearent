<?php

namespace App\Filament\Resources\TagResource\Pages;

use LaraZeus\Sky\Filament\Resources\TagResource\Pages\CreateTag as BaseCreateTag;
use App\Filament\Resources\TagResource;

class CreateTag extends BaseCreateTag
{
    protected static string $resource = TagResource::class;
}
