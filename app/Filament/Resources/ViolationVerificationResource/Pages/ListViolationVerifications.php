<?php

namespace App\Filament\Resources\ViolationVerificationResource\Pages;

use App\Filament\Resources\ViolationVerificationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;
use App\Models\YellowForm;

class ListViolationVerifications extends ListRecords
{
    protected static string $resource = ViolationVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Pages\ListRecords\Tab::make('All')
                ->badge(YellowForm::count()),
            'pending_dean' => \Filament\Resources\Pages\ListRecords\Tab::make('Pending Dean Verification')
                ->badge(YellowForm::where('dean_verification', false)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('dean_verification', false)),
            'approved_head' => \Filament\Resources\Pages\ListRecords\Tab::make('Approved by Head')
                ->badge(YellowForm::where('head_approval', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('head_approval', true)),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery();
    }
}
