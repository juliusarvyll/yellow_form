<?php

namespace App\Filament\Resources\ViolationResource\Pages;

use App\Filament\Resources\ViolationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateViolation extends CreateRecord
{
    protected static string $resource = ViolationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
