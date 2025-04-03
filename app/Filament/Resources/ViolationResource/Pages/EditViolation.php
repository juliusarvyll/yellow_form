<?php

namespace App\Filament\Resources\ViolationResource\Pages;

use App\Filament\Resources\ViolationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditViolation extends EditRecord
{
    protected static string $resource = ViolationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
