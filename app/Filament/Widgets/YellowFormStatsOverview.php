<?php

namespace App\Filament\Widgets;

use App\Models\YellowForm;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class YellowFormStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Get count of all yellow forms
        $totalCount = YellowForm::count();

        // Get count of forms from this month
        $monthlyCount = YellowForm::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Get count of repeat offenders (students with more than one form)
        $repeatOffendersCount = YellowForm::select('id_number')
            ->groupBy('id_number')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        // Get compliance rate (percentage of forms marked as complied)
        $complianceRate = YellowForm::count() > 0
            ? round((YellowForm::where('complied', true)->count() / YellowForm::count()) * 100)
            : 0;

        return [
            Stat::make('Total Yellow Forms', $totalCount)
                ->description('All time')
                ->descriptionIcon('heroicon-m-document-text'),

            Stat::make('This Month', $monthlyCount)
                ->description('Forms created in ' . now()->format('F'))
                ->descriptionIcon('heroicon-m-calendar'),

            Stat::make('Repeat Offenders', $repeatOffendersCount)
                ->description('Students with multiple forms')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Compliance Rate', $complianceRate . '%')
                ->description('Percentage of students who complied')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($complianceRate >= 70 ? 'success' : ($complianceRate >= 50 ? 'warning' : 'danger')),
        ];
    }
}
