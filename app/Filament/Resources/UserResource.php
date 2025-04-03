<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'System';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Role Assignment')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('User Role')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->searchable(),
                    ]),

                Forms\Components\Section::make('Department Access')
                    ->schema([
                        Forms\Components\Select::make('department_id')
                            ->label('Dean of Department')
                            ->relationship('department', 'department_name')
                            ->preload()
                            ->searchable()
                            ->helperText('Assign a department to give this user access to the Dean Panel.')
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    // Add Dean role if department is assigned
                                    $deanRoleId = Role::where('name', 'Dean')->first()?->id;
                                    if ($deanRoleId) {
                                        // Get current roles
                                        $roles = $set('roles') ?? [];

                                        // Add Dean role if not already in the array
                                        if (!in_array($deanRoleId, $roles)) {
                                            $roles[] = $deanRoleId;
                                            $set('roles', $roles);
                                        }
                                    }
                                }
                            })
                            ->live(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('primary')
                    ->separator(','),
                Tables\Columns\TextColumn::make('department.department_name')
                    ->label('Dean of Department')
                    ->placeholder('Not Assigned')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Role')
                    ->placeholder('All Roles')
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'department_name')
                    ->label('Department')
                    ->placeholder('All Users')
                    ->preload(),
                Tables\Filters\Filter::make('is_dean')
                    ->label('Is Dean')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('department_id'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('grant_super_admin')
                    ->label('Make Super Admin')
                    ->icon('heroicon-o-shield-check')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Grant Super Admin Privileges')
                    ->modalDescription('Are you sure you want to grant Super Admin privileges to this user? Super Admins have full access to the entire system.')
                    ->modalSubmitActionLabel('Yes, Grant Super Admin')
                    ->visible(fn (User $record) => !$record->hasRole('Super Admin'))
                    ->action(function (User $record) {
                        $record->assignRole('Super Admin');

                        \Filament\Notifications\Notification::make()
                            ->title('Super Admin privileges granted')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('revoke_super_admin')
                    ->label('Revoke Super Admin')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Revoke Super Admin Privileges')
                    ->modalDescription('Are you sure you want to revoke Super Admin privileges from this user?')
                    ->modalSubmitActionLabel('Yes, Revoke Super Admin')
                    ->visible(fn (User $record) => $record->hasRole('Super Admin'))
                    ->action(function (User $record) {
                        $record->removeRole('Super Admin');

                        \Filament\Notifications\Notification::make()
                            ->title('Super Admin privileges revoked')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('grantSuperAdmin')
                        ->label('Grant Super Admin')
                        ->icon('heroicon-o-shield-check')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Grant Super Admin Privileges')
                        ->modalDescription('Are you sure you want to grant Super Admin privileges to the selected users? Super Admins have full access to the entire system.')
                        ->modalSubmitActionLabel('Yes, Grant Super Admin')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = 0;

                            foreach ($records as $record) {
                                if (!$record->hasRole('Super Admin')) {
                                    $record->assignRole('Super Admin');
                                    $count++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("Super Admin privileges granted to {$count} users")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('revokeSuperAdmin')
                        ->label('Revoke Super Admin')
                        ->icon('heroicon-o-shield-exclamation')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Revoke Super Admin Privileges')
                        ->modalDescription('Are you sure you want to revoke Super Admin privileges from the selected users?')
                        ->modalSubmitActionLabel('Yes, Revoke Super Admin')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->hasRole('Super Admin')) {
                                    $record->removeRole('Super Admin');
                                    $count++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("Super Admin privileges revoked from {$count} users")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
