<?php

namespace App\Services\BaseClone\Games;

use App\Services\BaseClone\BuildingCatalog;

class CocHomeAdapter extends CocLayoutAdapter
{
    public function key(): string
    {
        return 'coc_home';
    }

    public function label(): string
    {
        return 'کلش آف کلنز — دهکدهٔ اصلی';
    }

    public function meta(): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'short' => 'دهکدهٔ اصلی',
            'game' => 'coc',
            'icon' => '🏰',
            'color' => 'amber',
            'result_type' => 'layout',
            'hint' => 'اسکرین‌شات کامل بیس (وار یا فارم). چیدمان ۴۴×۴۴ بازسازی می‌شود و اگر بیس در آرشیو باشد لینک کپی بازی را می‌گیرید.',
            'placeholder' => 'عکس بیس تاون‌هال',
        ];
    }

    protected function catalog(): BuildingCatalog
    {
        return new BuildingCatalog;
    }
}
