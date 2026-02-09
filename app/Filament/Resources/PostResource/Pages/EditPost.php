<?php

namespace App\Filament\Resources\PostResource\Pages;

use LaraZeus\Sky\Filament\Resources\PostResource\Pages\EditPost as BaseEditPost;
use App\Filament\Resources\PostResource;

class EditPost extends BaseEditPost
{
    protected static string $resource = PostResource::class;
}
