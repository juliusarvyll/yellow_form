<?php

namespace App\Filament\Resources\Dean\YellowFormResource\Pages;

use App\Filament\Resources\Dean\YellowFormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditYellowForm extends EditRecord
{
    protected static string $resource = YellowFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Automatically set both complied and dean_verification to true
        $data['complied'] = true;
        $data['dean_verification'] = true;

        // If there's no compliance date, set it to the current date
        if (empty($data['compliance_date'])) {
            $data['compliance_date'] = now();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Verification completed')
            ->body('You have successfully verified the student violation.');
    }
}
