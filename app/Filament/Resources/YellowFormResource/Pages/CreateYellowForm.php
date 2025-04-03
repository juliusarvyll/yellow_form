<?php

namespace App\Filament\Resources\YellowFormResource\Pages;

use App\Filament\Resources\YellowFormResource;
use Filament\Resources\Pages\CreateRecord;

class CreateYellowForm extends CreateRecord
{
    protected static string $resource = YellowFormResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
