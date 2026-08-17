<?php

namespace App\Filament\Widgets;

use App\Models\GameProfile;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentProfilesWidget extends BaseWidget
{
    protected static ?string $heading = '🏆 آخرین پروفایل‌ها و فرماندهان فعال کلش';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                GameProfile::query()->latest()->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('نام کاربری')
                    ->searchable()
                    ->default('فرمانده مهمان')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('player_tag')
                    ->label('تگ بازیکن')
                    ->copyable()
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-m-hashtag'),

                Tables\Columns\TextColumn::make('town_hall')
                    ->label('سطح تاون‌هال')
                    ->badge()
                    ->getStateUsing(fn ($record) => (int) ($record->game_data['townHallLevel'] ?? 1))
                    ->color(fn ($state): string => match (true) {
                        $state >= 16 => 'danger',
                        $state >= 14 => 'warning',
                        $state >= 12 => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => "TH {$state} 🏰"),

                Tables\Columns\TextColumn::make('trophies')
                    ->label('تروفی (کاپ)')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn ($record) => (int) ($record->game_data['trophies'] ?? 0))
                    ->icon('heroicon-m-trophy'),

                Tables\Columns\TextColumn::make('clan_name')
                    ->label('نام کلن')
                    ->getStateUsing(fn ($record) => (string) ($record->game_data['clan']['name'] ?? 'بدون کلن'))
                    ->default('بدون کلن')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-shield-check'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ]);
    }
}
