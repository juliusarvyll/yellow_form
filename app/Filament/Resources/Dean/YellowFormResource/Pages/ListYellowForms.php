<?php

namespace App\Filament\Resources\Dean\YellowFormResource\Pages;

use App\Filament\Resources\Dean\YellowFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYellowForms extends ListRecords
{
    protected static string $resource = YellowFormResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
