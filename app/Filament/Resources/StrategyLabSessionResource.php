<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StrategyLabSessionResource\Pages;
use App\Filament\Resources\StrategyLabSessionResource\RelationManagers;
use App\Models\StrategyLabSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StrategyLabSessionResource extends Resource
{
    protected static ?string $model = StrategyLabSession::class;

    protected static ?string $navigationLabel = 'جلسات آزمایشگاه استراتژی';
    protected static ?string $modelLabel = 'جلسه آزمایشگاه';
    protected static ?string $pluralModelLabel = 'جلسات آزمایشگاه';
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('title')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('image_path')
                    ->image()
                    ->required(),
                Forms\Components\TextInput::make('buildings')
                    ->required(),
                Forms\Components\TextInput::make('analysis'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image_path'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStrategyLabSessions::route('/'),
            'create' => Pages\CreateStrategyLabSession::route('/create'),
            'edit' => Pages\EditStrategyLabSession::route('/{record}/edit'),
        ];
    }
}
