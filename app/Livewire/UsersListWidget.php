<?php

namespace App\Livewire;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsersListWidget extends TableWidget
{
    public function table(Table $table): Table
    {
        return UserResource::table($table)
            ->heading('Admins List')
            ->query(
                UserResource::getEloquentQuery()
                    ->role(['super_admin', 'admin', 'staff'])
            )
            ->headerActions([
                Action::make('create_user')
                    ->label('New Admin')
                    ->modalHeading('New Admin')
                    ->form([
                        Select::make('user_type')
                            ->label('User Source')
                            ->options([
                                'new' => 'Create New User',
                                'existing' => 'Select Existing Customer',
                            ])
                            ->default('new')
                            ->live()
                            ->afterStateUpdated(function ($get, $set) {
                                if ($get('user_type') === 'existing') {
                                    $set('password', null);
                                    $set('name', null);
                                    $set('email', null);
                                }
                            }),
                        
                        // Fields for New User
                        TextInput::make('name')
                            ->required()
                            ->visible(fn ($get) => $get('user_type') === 'new'),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique('users', 'email')
                            ->visible(fn ($get) => $get('user_type') === 'new'),
                        TextInput::make('password')
                            ->password()
                            ->required()
                            ->visible(fn ($get) => $get('user_type') === 'new'),

                        // Fields for Existing User
                        Select::make('user_id')
                            ->label('Select Customer')
                            ->options(function () {
                                return User::whereDoesntHave('roles', function ($query) {
                                    $query->whereIn('name', ['super_admin', 'admin', 'staff']);
                                })->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->visible(fn ($get) => $get('user_type') === 'existing'),

                        // Common Fields (Roles)
                        Select::make('roles')
                            ->options(Role::pluck('name', 'id'))
                            ->multiple()
                            ->required()
                            ->preload(),
                    ])
                    ->action(function (array $data) {
                        if ($data['user_type'] === 'new') {
                            $user = User::create([
                                'name' => $data['name'],
                                'email' => $data['email'],
                                'password' => Hash::make($data['password']),
                            ]);
                        } else {
                            $user = User::find($data['user_id']);
                        }
                        
                        if (!empty($data['roles'])) {
                            $user->syncRoles($data['roles']);
                        }
                        
                        // Redirect to edit page
                        return redirect(UserResource::getUrl('edit', ['record' => $user]));
                    }),
            ]);
    }
}
