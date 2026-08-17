<?php

namespace App\Filament\Resources\StrategyLabSessionResource\Pages;

use App\Filament\Resources\StrategyLabSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStrategyLabSessions extends ListRecords
{
    protected static string $resource = StrategyLabSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
