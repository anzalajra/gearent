<?php

namespace App\Filament\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use LaraZeus\Sky\Filament\Resources\PageResource as ZeusPageResource;
use LaraZeus\Sky\SkyPlugin;
use Illuminate\Support\Str;
use Filament\Tables\Table;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use LaraZeus\Sky\Models\Post;

class PageResource extends ZeusPageResource
{
    protected static ?string $slug = 'pages';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getModel(): string
    {
        return SkyPlugin::get()->getModel('Post');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ViewColumn::make('title_card')
                    ->label(__('zeus-sky::cms.page.title'))
                    ->sortable(['title'])
                    ->searchable(['title'])
                    ->toggleable()
                    ->view('zeus::filament.columns.page-title'),

                TextColumn::make('status')
                    ->label(__('zeus-sky::cms.page.status'))
                    ->sortable(['status'])
                    ->searchable(['status'])
                    ->toggleable()
                    ->tooltip(fn (Post $record): string => $record->published_at->format('Y/m/d | H:i A'))
                    ->description(fn ($record) => optional($record->published_at)->diffForHumans()),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                EditAction::make('edit')->label('Edit'),
                Action::make('view_page')
                    ->label('View Page')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('page', $record->slug))
                    ->openUrlInNewTab(),
                DeleteAction::make('delete'),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->multiple()
                    ->label(__('zeus-sky::cms.page.status'))
                    ->options(SkyPlugin::get()->getEnum('PostStatus')),
                Filter::make('password')
                    ->label(__('zeus-sky::cms.page.password'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('password')),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('post_tabs')
                ->columnSpan(2)
                ->schema([
                    Tab::make(__('zeus-sky::cms.common.title_content'))
                        ->schema([
                            TextInput::make('title')
                                ->label(__('zeus-sky::cms.page.title'))
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $set('slug', Str::slug($state));
                                }),
                            config('zeus-sky.editor')::component(),
                        ]),
                    Tab::make(__('zeus-sky::cms.common.SEO'))
                        ->schema([
                            Hidden::make('user_id')
                                ->required()
                                ->default(fn () => \Illuminate\Support\Facades\Auth::id()),

                            Hidden::make('post_type')
                                ->default('page')
                                ->required(),

                            Textarea::make('description')
                                ->maxLength(255)
                                ->label(__('zeus-sky::cms.page.description'))
                                ->hint(__('zeus-sky::cms.page.description_hint')),

                            TextInput::make('slug')
                                ->unique(ignoreRecord: true)
                                ->required()
                                ->maxLength(255)
                                ->label(__('zeus-sky::cms.page.slug')),

                            Select::make('parent_id')
                                ->options(SkyPlugin::get()->getModel('Post')::where('post_type', 'page')->pluck(
                                    'title',
                                    'id'
                                ))
                                ->label(__('zeus-sky::cms.page.parent_page')),

                            TextInput::make('ordering')
                                ->integer()
                                ->label(__('zeus-sky::cms.page.page_order'))
                                ->default(1),
                        ]),
                    Tab::make(__('zeus-sky::cms.common.visibility'))
                        ->schema([
                            Select::make('status')
                                ->label(__('zeus-sky::cms.page.status'))
                                ->default('publish')
                                ->required()
                                ->live()
                                ->options(SkyPlugin::get()->getEnum('PostStatus')),

                            TextInput::make('password')
                                ->label(__('zeus-sky::cms.page.password'))
                                ->visible(fn (Get $get): bool => $get('status') === 'private'),

                            DateTimePicker::make('published_at')
                                ->label(__('zeus-sky::cms.page.published_at'))
                                ->required()
                                ->default(now()),

                            Toggle::make('options.show_title')
                                ->label('Show Page Title in Frontend')
                                ->default(true),
                        ]),
                    Tab::make(__('zeus-sky::cms.common.image'))
                        ->schema([
                            ToggleButtons::make('featured_image_type')
                                ->dehydrated(false)
                                ->hiddenLabel()
                                ->live()
                                ->afterStateHydrated(function (Set $set, Get $get) {
                                    $setVal = ($get('featured_image') === null) ? 'upload' : 'url';
                                    $set('featured_image_type', $setVal);
                                })
                                ->grouped()
                                ->options([
                                    'upload' => __('zeus-sky::cms.page.upload'),
                                    'url' => __('zeus-sky::cms.page.url'),
                                ])
                                ->default('upload'),
                            SpatieMediaLibraryFileUpload::make('featured_image_upload')
                                ->collection('pages')
                                ->disk(SkyPlugin::get()->getUploadDisk())
                                ->directory(SkyPlugin::get()->getUploadDirectory())
                                ->visible(fn (Get $get) => $get('featured_image_type') === 'upload')
                                ->label(''),
                            TextInput::make('featured_image')
                                ->label(__('zeus-sky::cms.page.featured_image_url'))
                                ->visible(fn (Get $get) => $get('featured_image_type') === 'url')
                                ->url(),
                        ]),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\PageResource\Pages\ListPage::route('/'),
            'create' => \App\Filament\Resources\PageResource\Pages\CreatePage::route('/create'),
            'edit' => \App\Filament\Resources\PageResource\Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
