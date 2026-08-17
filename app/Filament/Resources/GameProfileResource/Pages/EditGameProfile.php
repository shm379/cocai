<?php

namespace App\Filament\Resources\GameProfileResource\Pages;

use App\Filament\Resources\GameProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameProfile extends EditRecord
{
    protected static string $resource = GameProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
