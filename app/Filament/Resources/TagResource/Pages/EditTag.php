<?php

namespace App\Filament\Resources\TagResource\Pages;

use LaraZeus\Sky\Filament\Resources\TagResource\Pages\EditTag as BaseEditTag;
use App\Filament\Resources\TagResource;

class EditTag extends BaseEditTag
{
    protected static string $resource = TagResource::class;
}
