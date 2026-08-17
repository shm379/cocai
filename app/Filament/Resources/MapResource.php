<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MapResource\Pages;
use App\Filament\Resources\MapResource\RelationManagers;
use App\Models\Map;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MapResource extends Resource
{
    protected static ?string $model = Map::class;

    protected static ?string $navigationLabel = 'نقشه‌ها و مپ‌ها';
    protected static ?string $modelLabel = 'نقشه';
    protected static ?string $pluralModelLabel = 'نقشه‌ها';
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('image_url')
                    ->image(),
                Forms\Components\TextInput::make('thumbnail_url')
                    ->maxLength(255),
                Forms\Components\TextInput::make('map_link')
                    ->maxLength(255),
                Forms\Components\TextInput::make('copy_link')
                    ->maxLength(255),
                Forms\Components\TextInput::make('view_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('download_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('like_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('report_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image_url'),
                Tables\Columns\TextColumn::make('thumbnail_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('map_link')
                    ->searchable(),
                Tables\Columns\TextColumn::make('copy_link')
                    ->searchable(),
                Tables\Columns\TextColumn::make('view_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('download_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('like_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_count')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListMaps::route('/'),
            'create' => Pages\CreateMap::route('/create'),
            'edit' => Pages\EditMap::route('/{record}/edit'),
        ];
    }
}
