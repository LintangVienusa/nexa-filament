<?php

namespace App\Filament\Resources\BastProjectResource\Pages;

use App\Filament\Resources\BastProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBastProject extends CreateRecord
{
    protected static string $resource = BastProjectResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
