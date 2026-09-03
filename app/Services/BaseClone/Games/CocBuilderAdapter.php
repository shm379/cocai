<?php

namespace App\Services\BaseClone\Games;

use App\Services\BaseClone\BuilderBaseCatalog;
use App\Services\BaseClone\BuildingCatalog;

class CocBuilderAdapter extends CocLayoutAdapter
{
    public function key(): string
    {
        return 'coc_builder';
    }

    public function label(): string
    {
        return 'کلش آف کلنز — بیلدر بیس';
    }

    public function meta(): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'short' => 'بیلدر بیس',
            'game' => 'coc',
            'icon' => '🔨',
            'color' => 'orange',
            'result_type' => 'layout',
            'hint' => 'اسکرین‌شات بیلدر بیس (هر دو مرحله در یک عکس بهتر است). چیدمان با ابعاد تقریبی ساختمان‌های بیلدر بیس بازسازی می‌شود.',
            'placeholder' => 'عکس بیلدر بیس',
        ];
    }

    public function catalog(): BuildingCatalog
    {
        return new BuilderBaseCatalog;
    }
}
