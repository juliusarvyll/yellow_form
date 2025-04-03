<?php

namespace App\Filament\Resources\ViolationVerificationResource\Pages;

use App\Filament\Resources\ViolationVerificationResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewViolationVerification extends ViewRecord
{
    protected static string $resource = ViolationVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
