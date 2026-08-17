<?php

namespace App\Filament\Resources\GameProfileResource\Pages;

use App\Filament\Resources\GameProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGameProfiles extends ListRecords
{
    protected static string $resource = GameProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
