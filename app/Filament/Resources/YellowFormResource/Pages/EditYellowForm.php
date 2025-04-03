<?php

namespace App\Filament\Resources\YellowFormResource\Pages;

use App\Filament\Resources\YellowFormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYellowForm extends EditRecord
{
    protected static string $resource = YellowFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
