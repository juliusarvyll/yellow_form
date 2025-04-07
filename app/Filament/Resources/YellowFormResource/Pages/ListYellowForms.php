<?php

namespace App\Filament\Resources\YellowFormResource\Pages;

use App\Filament\Resources\YellowFormResource;
use App\Models\Department;
use App\Models\YellowForm;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYellowForms extends ListRecords
{
    protected static string $resource = YellowFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => \Filament\Resources\Pages\ListRecords\Tab::make('All Forms')
                ->badge(function () {
                    return YellowForm::count();
                }),
            'multiple_violations' => \Filament\Resources\Pages\ListRecords\Tab::make('Multiple Violations')
                ->modifyQueryUsing(function ($query) {
                    $subquery = YellowForm::selectRaw('id_number, COUNT(*) as form_count')
                        ->groupBy('id_number')
                        ->having('form_count', '>=', 2)
                        ->pluck('id_number');

                    return $query->whereIn('id_number', $subquery);
                })
                ->badge(function () {
                    return YellowForm::selectRaw('id_number')
                        ->groupBy('id_number')
                        ->havingRaw('COUNT(*) >= 2')
                        ->count();
                }),
            'students_with_3_forms' => \Filament\Resources\Pages\ListRecords\Tab::make('3+ Violations')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(function ($query) {
                    $subquery = YellowForm::selectRaw('id_number, COUNT(*) as form_count')
                        ->groupBy('id_number')
                        ->having('form_count', '>=', 3)
                        ->pluck('id_number');

                    return $query->whereIn('id_number', $subquery);
                })
                ->badge(function () {
                    return YellowForm::selectRaw('id_number')
                        ->groupBy('id_number')
                        ->havingRaw('COUNT(*) >= 3')
                        ->count();
                })
                ->badgeColor('danger'),
        ];

        // Add a tab for each department that has violations
        $departments = Department::orderBy('department_name')->get();

        foreach ($departments as $department) {
            $violationCount = YellowForm::where('department_id', $department->id)->count();

            // Only add tabs for departments that have violations
            if ($violationCount > 0) {
                $tabs[$department->id] = \Filament\Resources\Pages\ListRecords\Tab::make($department->department_name)
                    ->modifyQueryUsing(fn ($query) => $query->where('department_id', $department->id))
                    ->badge($violationCount);
            }
        }

        return $tabs;
    }
}
