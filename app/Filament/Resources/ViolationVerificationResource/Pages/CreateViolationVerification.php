<?php

namespace App\Filament\Resources\ViolationVerificationResource\Pages;

use App\Filament\Resources\ViolationVerificationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateViolationVerification extends CreateRecord
{
    protected static string $resource = ViolationVerificationResource::class;

    // Redirect to the list page after creation
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
