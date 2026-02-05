<?php

namespace App\Filament\Fabricator\PageBlocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Z3d0X\FilamentFabricator\PageBlocks\PageBlock;

class HowItWorks extends PageBlock
{
    public static string $name = 'how-it-works';

    public static function defineBlock(Block $block): Block
    {
        return $block
            ->schema([
                TextInput::make('heading')
                    ->default('How It Works'),
                Repeater::make('steps')
                    ->schema([
                        FileUpload::make('icon')
                            ->image()
                            ->directory('how-it-works-icons'),
                        TextInput::make('title')
                            ->required(),
                        Textarea::make('description')
                            ->rows(3),
                    ])
                    ->columns(3)
                    ->minItems(1),
            ]);
    }

    public static function mutateData(array $data): array
    {
        return $data;
    }
}
