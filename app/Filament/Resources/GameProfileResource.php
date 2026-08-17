<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameProfileResource\Pages;
use App\Filament\Resources\GameProfileResource\RelationManagers;
use App\Models\GameProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GameProfileResource extends Resource
{
    protected static ?string $model = GameProfile::class;

    protected static ?string $navigationLabel = 'پروفایل‌های کلش';
    protected static ?string $modelLabel = 'پروفایل کلش';
    protected static ?string $pluralModelLabel = 'پروفایل‌های کلش';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('player_tag')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('game_data')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('player_tag')
                    ->searchable(),
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
            'index' => Pages\ListGameProfiles::route('/'),
            'create' => Pages\CreateGameProfile::route('/create'),
            'edit' => Pages\EditGameProfile::route('/{record}/edit'),
        ];
    }
}
