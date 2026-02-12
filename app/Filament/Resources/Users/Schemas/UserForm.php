<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use App\Models\CustomerCategory;
use App\Models\Setting;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $customFields = json_decode(Setting::get('registration_custom_fields', '[]'), true);
        $customComponents = [];

        if (!empty($customFields)) {
            foreach ($customFields as $field) {
                $fieldName = 'custom_fields.' . $field['name'];
                $label = $field['label'];
                $component = null;

                switch ($field['type']) {
                    case 'text':
                    case 'email':
                    case 'number':
                        $component = TextInput::make($fieldName)
                            ->label($label)
                            ->numeric($field['type'] === 'number')
                            ->email($field['type'] === 'email');
                        break;
                    case 'textarea':
                        $component = Textarea::make($fieldName)->label($label);
                        break;
                    case 'select':
                        $options = collect($field['options'] ?? [])->pluck('label', 'value');
                        $component = Select::make($fieldName)
                            ->label($label)
                            ->options($options);
                        break;
                    case 'radio':
                        $options = collect($field['options'] ?? [])->pluck('label', 'value');
                        $component = Radio::make($fieldName)
                            ->label($label)
                            ->options($options);
                        break;
                    case 'checkbox':
                        $component = Checkbox::make($fieldName)->label($label);
                        break;
                }

                if ($component) {
                    if ($field['required'] ?? false) {
                        $component->required();
                    }
                    $customComponents[] = $component;
                }
            }
        }

        return $schema
            ->components([
                Tabs::make('User Details')
                    ->tabs([
                        Tab::make('Customer Information')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20),
                                Select::make('customer_category_id')
                                    ->label('Category')
                                    ->options(CustomerCategory::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live(),
                                TextInput::make('nik')
                                    ->label('NIK / KTP')
                                    ->maxLength(255),
                                Textarea::make('address')
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Tab::make('Additional Information')
                            ->schema($customComponents)
                            ->columns(2)
                            ->visible(count($customComponents) > 0),

                        Tab::make('Account')
                            ->schema([
                                DateTimePicker::make('email_verified_at'),
                                TextInput::make('password')
                                    ->password()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrated(fn (?string $state) => filled($state))
                                    ->maxLength(255)
                                    ->suffixAction(
                                        \Filament\Actions\Action::make('resetPassword')
                                            ->icon('heroicon-o-arrow-path')
                                            ->color('warning')
                                            ->requiresConfirmation()
                                            ->modalHeading('Reset Password')
                                            ->modalDescription('Are you sure you want to reset this user\'s password to "resetpassword"?')
                                            ->action(function ($record) {
                                                if (!$record) return;
                                                $record->update([
                                                    'password' => 'resetpassword', // Ideally hashed, but User model casts password to hashed
                                                ]);
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Password reset successfully')
                                                    ->success()
                                                    ->send();
                                            })
                                            ->visible(fn ($record) => $record !== null)
                                            ->tooltip('Reset Password to "resetpassword"')
                                    ),
                                Select::make('roles')
                                    ->relationship('roles', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable(),
                                Toggle::make('is_verified')
                                    ->label('Verified Customer')
                                    ->default(false),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }
}
