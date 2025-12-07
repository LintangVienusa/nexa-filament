<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Pages\Actions\Action;
use Filament\Notifications\Notification;
use Spatie\Activitylog\Models\Activity;

class EditLeave extends EditRecord
{
    protected static string $resource = LeaveResource::class;

    

    public static function edit(Leave $record): static
    {
        return parent::edit($record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mount($record): void
    {
        parent::mount($record);

        // Cek status invoice
        if ($this->record->invoice?->status === '2') { // 2 = Paid
            

            // Redirect user ke halaman index agar tidak tetap di form edit
            $this->redirect($this->getResource()::getUrl('index'));

            $activity = activity('Leaves-action')
            ->causedBy(auth()->user())
            ->withProperties([
                'ip' =>  Leave()->ip(),
                'menu' => 'Leaves Items',
                'email' => auth()->user()->email,
                'record_id' => $record->id,
                'record_name' => $record->name ?? null,
            ])
            ->log('Membuka halaman Edit Leaves');

            Activity::latest()->first()->update([
                'email' => auth()->user()?->email,
                'menu' => 'Leaves Items',
            ]);
        }
    }
}
