<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaAssetResource\Pages;
use App\Models\MediaAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class MediaAssetResource extends Resource
{
    protected static ?string $model = MediaAsset::class;

    protected static ?string $navigationGroup = '🗂️ مدیریت فایل‌ها و مدیا';
    protected static ?string $navigationLabel = 'کتابخانه فایل‌ها و مدیا';
    protected static ?string $modelLabel = 'فایل / مدیا';
    protected static ?string $pluralModelLabel = 'فایل‌ها و مدیاها';
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بارگذاری و مشخصات فایل')
                    ->description('تصاویر نقشه‌ها، آیکن‌ها، بنرها و فایل‌های مورد نیاز سامانه')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('انتخاب و آپلود فایل')
                            ->directory('uploads/media')
                            ->disk('public')
                            ->image()
                            ->imageEditor()
                            ->required()
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $name = is_string($state) ? basename($state) : 'media_file';
                                    $set('name', $name);
                                    $url = Storage::disk('public')->url($state);
                                    $set('file_url', $url);
                                }
                            }),

                        Forms\Components\TextInput::make('name')
                            ->label('عنوان یا نام فایل')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('category')
                            ->label('دسته‌بندی مدیا')
                            ->options([
                                'maps' => '🗺️ نقشه‌های تاون‌هال و بیلدرهال',
                                'troops' => '🧪 آیکن نیروها و اسپل‌ها',
                                'heroes' => '👑 تجهیزات و پت‌های هیرو',
                                'banners' => '🎨 بنرها و پس‌زمینه‌ها',
                                'general' => '📁 سایر فایل‌ها',
                            ])
                            ->default('general')
                            ->required(),

                        Forms\Components\TextInput::make('alt_text')
                            ->label('متن جایگزین (Alt Text) برای سئو')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('file_url')
                            ->label('آدرس مستقیم فایل (URL / CDN)')
                            ->maxLength(500),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('پیش‌نمایش')
                    ->disk('public')
                    ->square()
                    ->size(60),

                Tables\Columns\TextColumn::make('name')
                    ->label('نام فایل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->label('دسته‌بندی')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'maps' => 'warning',
                        'troops' => 'info',
                        'heroes' => 'danger',
                        'banners' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'maps' => '🗺️ نقشه‌ها',
                        'troops' => '🧪 نیروها',
                        'heroes' => '👑 هیروها',
                        'banners' => '🎨 بنرها',
                        default => '📁 عمومی',
                    }),

                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('حجم فایل')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ آپلود')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('فیلتر دسته‌بندی')
                    ->options([
                        'maps' => 'نقشه‌ها',
                        'troops' => 'نیروها',
                        'heroes' => 'هیروها',
                        'banners' => 'بنرها',
                        'general' => 'عمومی',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('copy_url')
                    ->label('کپی لینک')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->action(function (MediaAsset $record) {
                        Notification::make()
                            ->title('لینک فایل کپی شد')
                            ->body($record->file_url ?? Storage::disk('public')->url($record->file_path))
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListMediaAssets::route('/'),
            'create' => Pages\CreateMediaAsset::route('/create'),
            'edit' => Pages\EditMediaAsset::route('/{record}/edit'),
        ];
    }
}
