<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use App\Models\Profile;
use Illuminate\View\View;

class EditProfile extends EditRecord
{
    protected static string $resource = ProfileResource::class;
     public function getHeader(): ?View
    {
         $employee = auth()->user()->employee;

        return view('profile-header', [
            'photo' => $employee->file_photo ? asset('storage/' . $employee->file_photo) : asset('images/default-avatar.png'),
            'name' => $employee->full_name ?? '-',
            'division' => $employee->organization->divisi_name ?? '-',
            'unit' => $employee->organization->unit_name ?? '-',
            'position' => $employee->job_title ?? '-',
        ]);
    }


    public function getBreadcrumbs(): array
    {
        return []; 
    }

    public  function getTitle(): string
    {
        return 'Profil Saya';
    }

    protected function resolveRecord($key): Model
    {
        $email = urldecode($key ?? auth()->user()?->email);

        return Profile::where('email', $email)->firstOrFail();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => urlencode(auth()->user()?->email)]);
    }
}
