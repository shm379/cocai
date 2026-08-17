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
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('پیش‌نمایش نقشه')
                    ->circular(false)
                    ->square(),

                Tables\Columns\TextColumn::make('name')
                    ->label('عنوان نقشه')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('بازدید')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-eye'),

                Tables\Columns\TextColumn::make('like_count')
                    ->label('لایک')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-m-heart'),

                Tables\Columns\TextColumn::make('download_count')
                    ->label('کپی')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-arrow-down-tray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ درج')
                    ->dateTime('Y/m/d')
                    ->sortable(),
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
