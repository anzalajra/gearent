<?php

namespace App\Filament\Resources;

use LaraZeus\Sky\Filament\Resources\TagResource as ZeusTagResource;
use LaraZeus\Sky\SkyPlugin;
use App\Filament\Resources\TagResource\Pages\CreateTag;
use App\Filament\Resources\TagResource\Pages\EditTag;
use App\Filament\Resources\TagResource\Pages\ListTags;

class TagResource extends ZeusTagResource
{
    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getModel(): string
    {
        return SkyPlugin::get()->getModel('Tag');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
        ];
    }
}
