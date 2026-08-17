<?php

namespace App\Filament\Resources\MetaTierItemResource\Pages;

use App\Filament\Resources\MetaTierItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMetaTierItems extends ListRecords
{
    protected static string $resource = MetaTierItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
