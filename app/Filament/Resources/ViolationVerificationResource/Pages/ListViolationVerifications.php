<?php

namespace App\Filament\Resources\ViolationVerificationResource\Pages;

use App\Filament\Resources\ViolationVerificationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;

class ListViolationVerifications extends ListRecords
{
    protected static string $resource = ViolationVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery();
    }
}
