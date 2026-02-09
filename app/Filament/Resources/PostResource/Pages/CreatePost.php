<?php

namespace App\Filament\Resources\PostResource\Pages;

use LaraZeus\Sky\Filament\Resources\PostResource\Pages\CreatePost as BaseCreatePost;
use App\Filament\Resources\PostResource;

class CreatePost extends BaseCreatePost
{
    protected static string $resource = PostResource::class;
}
