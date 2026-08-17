<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MetaTierItemResource\Pages;
use App\Models\MetaTierItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MetaTierItemResource extends Resource
{
    protected static ?string $model = MetaTierItem::class;

    protected static ?string $navigationGroup = '⚔️ استراتژی‌های پیروزی و متا';
    protected static ?string $navigationLabel = 'تیر لیست متای برتر (Tier List)';
    protected static ?string $modelLabel = 'استراتژی و متای پیروزی';
    protected static ?string $pluralModelLabel = 'استراتژی‌ها و ترکیب‌های متای برتر';
    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('مشخصات متای برتر و ترکیب')
                    ->description('اطلاعات ترکیب‌ها، تجهیزات و شگردهای ۳ ستاره وار متای ۲۰۲۶')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان استراتژی / ترکیب')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('category')
                            ->label('دسته‌بندی')
                            ->options([
                                'army' => '⚔️ ارتش و ترکیب نیروها',
                                'equipment' => '👑 لوداوت تجهیزات هیرو',
                                'attack_combo' => '⚡ شگرد حمله (بلیمپ/هیرو دایو)',
                                'base_defense' => '🛡️ چیدمان دفاعی آنتی متا',
                            ])
                            ->required(),

                        Forms\Components\Select::make('tier')
                            ->label('رتبه در تیرلیست (Tier)')
                            ->options([
                                'S_PLUS' => '🔥 S+ Tier (گادمود - مرگبارترین)',
                                'S' => '⭐ S Tier (فوق‌العاده قدرتمند)',
                                'A' => '✨ A Tier (بسیار خوب و پایدار)',
                                'B' => '⚡ B Tier (شرایطی و سرگرم‌کننده)',
                            ])
                            ->default('S_PLUS')
                            ->required(),

                        Forms\Components\TextInput::make('win_rate_percentage')
                            ->label('درصد احتمال برد و ۳ ستاره')
                            ->numeric()
                            ->suffix('%')
                            ->default(95)
                            ->required(),

                        Forms\Components\TextInput::make('difficulty_rating')
                            ->label('درجه سختی اجرا (۱ تا ۵)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->default(2)
                            ->required(),

                        Forms\Components\TextInput::make('town_hall_min')
                            ->label('حداقل تاون‌هال')
                            ->numeric()
                            ->default(11)
                            ->required(),

                        Forms\Components\TextInput::make('town_hall_max')
                            ->label('حداکثر تاون‌هال')
                            ->numeric()
                            ->default(18)
                            ->required(),

                        Forms\Components\TextInput::make('army_link')
                            ->label('لینک مستقیم کپی ارتش در بازی (Copy Army Link)')
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('tactical_brief_fa')
                            ->label('خلاصه تاکتیکی و نحوه اجرای ۳ ستاره وار')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('نمایش ویژه در هاب داشبورد گیمرها')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان استراتژی')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('tier')
                    ->label('رتبه متا')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'S_PLUS' => 'danger',
                        'S' => 'warning',
                        'A' => 'success',
                        'B' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'S_PLUS' => '🔥 S+ Tier',
                        'S' => '⭐ S Tier',
                        'A' => '✨ A Tier',
                        'B' => '⚡ B Tier',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('category')
                    ->label('دسته‌بندی')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'army' => '⚔️ ارتش',
                        'equipment' => '👑 تجهیزات',
                        'attack_combo' => '⚡ شگرد حمله',
                        'base_defense' => '🛡️ بیس دفاعی',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('win_rate_percentage')
                    ->label('نرخ برد')
                    ->formatStateUsing(fn ($state) => "{$state}%")
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('ویژه')
                    ->boolean(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('بازدید')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('copies_count')
                    ->label('دفعات کپی')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tier')
                    ->label('فیلتر رتبه متا')
                    ->options([
                        'S_PLUS' => '🔥 S+ Tier',
                        'S' => '⭐ S Tier',
                        'A' => '✨ A Tier',
                        'B' => '⚡ B Tier',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->label('فیلتر دسته‌بندی')
                    ->options([
                        'army' => 'ارتش',
                        'equipment' => 'تجهیزات',
                        'attack_combo' => 'شگرد حمله',
                        'base_defense' => 'بیس دفاعی',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMetaTierItems::route('/'),
            'create' => Pages\CreateMetaTierItem::route('/create'),
            'edit' => Pages\EditMetaTierItem::route('/{record}/edit'),
        ];
    }
}
