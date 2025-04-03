<?php

namespace App\Filament\Resources\Dean\YellowFormResource\Pages;

use App\Filament\Resources\Dean\YellowFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;

class ListYellowForms extends ListRecords
{
    protected static string $resource = YellowFormResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public  function getTabs(): array
    {
        return [
            'pending' => ListRecords\Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($query) {
                    $query->where('dean_verification', false)
                        ->orWhereNull('dean_verification');
                }))
                ->icon('heroicon-o-clock')
                ->badge(fn () => YellowFormResource::getEloquentQuery()
                    ->where(function ($query) {
                        $query->where('dean_verification', false)
                            ->orWhereNull('dean_verification');
                    })
                    ->count()
                ),
            'approved' => ListRecords\Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('dean_verification', true))
                ->icon('heroicon-o-check-circle')
                ->badge(fn () => YellowFormResource::getEloquentQuery()
                    ->where('dean_verification', true)
                    ->count()
                ),
        ];
    }
}
