<?php

namespace App\Filament\Resources\Dean\YellowFormResource\Pages;

use App\Filament\Resources\Dean\YellowFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewYellowForm extends ViewRecord
{
    protected static string $resource = YellowFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Approve'),
        ];
    }
}
