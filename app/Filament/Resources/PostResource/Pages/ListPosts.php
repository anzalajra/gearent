<?php

namespace App\Filament\Resources\PostResource\Pages;

use LaraZeus\Sky\Filament\Resources\PostResource\Pages\ListPosts as BaseListPosts;
use App\Filament\Resources\PostResource;

class ListPosts extends BaseListPosts
{
    protected static string $resource = PostResource::class;
}
