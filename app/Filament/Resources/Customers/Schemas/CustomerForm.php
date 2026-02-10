<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Models\CustomerCategory;
use App\Models\Setting;

class CustomerForm
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

                    $visibleCats = $field['visible_for_categories'] ?? [];
                    if (!empty($visibleCats)) {
                        $component->visible(function ($get) use ($visibleCats) {
                            $catId = $get('customer_category_id');
                            // Show if no category selected yet (or maybe hide?), or if category matches
                            // In admin edit, we want to see it if the customer has that category
                            return !empty($catId) && in_array($catId, $visibleCats);
                        });
                    }

                    $customComponents[] = $component;
                }
            }
        }

        return $schema
            ->components([
                Section::make('Customer Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique('customers', 'email', ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),

                        Select::make('customer_category_id')
                            ->label('Category')
                            ->options(CustomerCategory::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live(), // Make it live to trigger visibility updates

                        TextInput::make('nik')
                            ->label('NIK (No. KTP)')
                            ->maxLength(16)
                            ->minLength(16),

                        Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Additional Information')
                    ->schema($customComponents)
                    ->columns(2)
                    ->visible(count($customComponents) > 0),
            ]);
    }
}