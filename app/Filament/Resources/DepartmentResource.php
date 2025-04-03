<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'System';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('department_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('courses_count')
                    ->counts('courses')
                    ->label('Courses'),
                Tables\Columns\TextColumn::make('yellowForms_count')
                    ->counts('yellowForms')
                    ->label('Yellow Forms'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Department $record, Tables\Actions\DeleteAction $action) {
                        if ($record->courses()->count() > 0 || $record->yellowForms()->count() > 0) {
                            $action->cancel();
                            $action->notification()->danger('Cannot delete department that has courses or yellow forms.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Department $records, Tables\Actions\DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                if ($record->courses()->count() > 0 || $record->yellowForms()->count() > 0) {
                                    $action->cancel();
                                    $action->notification()->danger('Cannot delete departments that have courses or yellow forms.');
                                    break;
                                }
                            }
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
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
