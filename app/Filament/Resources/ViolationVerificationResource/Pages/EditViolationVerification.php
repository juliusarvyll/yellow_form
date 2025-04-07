<?php

namespace App\Filament\Resources\ViolationVerificationResource\Pages;

use App\Filament\Resources\ViolationVerificationResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use App\Models\YellowForm;

class EditViolationVerification extends EditRecord
{
    protected static string $resource = ViolationVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Create a notification message based on which fields were updated
        $message = 'Verification updated';
        $updates = [];

        if ($record->wasChanged('complied')) {
            $updates[] = $record->complied ? 'marked as complied' : 'marked as not complied';
        }

        if ($record->wasChanged('dean_verification')) {
            $updates[] = $record->dean_verification ? 'dean verification confirmed' : 'dean verification removed';
        }

        if ($record->wasChanged('head_approval')) {
            $updates[] = $record->head_approval ? 'approved by head' : 'disapproved by head';
        }

        if (!empty($updates)) {
            $message = 'Verification: ' . implode(', ', $updates);
        }

        // Show a success notification
        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }

    public function canEdit(YellowForm $record): bool
    {
        return $record->complied && $record->dean_verification;
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $record = $this->getRecord();

        // Redirect to view if conditions not met
        if (!$record->complied && !$record->dean_verification) {
            redirect()->to(ViolationVerificationResource::getUrl('view', ['record' => $record]));
        }
    }
}
