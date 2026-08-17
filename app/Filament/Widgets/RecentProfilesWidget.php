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
                    ->color(fn (int $state): string => match (true) {
                        $state >= 16 => 'danger',
                        $state >= 14 => 'warning',
                        $state >= 12 => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => "TH {$state} 🏰"),

                Tables\Columns\TextColumn::make('trophies')
                    ->label('تروفی (کاپ)')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-trophy'),

                Tables\Columns\TextColumn::make('clan_name')
                    ->label('نام کلن')
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
