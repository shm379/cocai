<?php

namespace App\Filament\Resources\MetaTierItemResource\Pages;

use App\Filament\Resources\MetaTierItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMetaTierItem extends EditRecord
{
    protected static string $resource = MetaTierItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
