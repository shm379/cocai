<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

class CrawlClasherMaps implements ShouldQueue
{
    use Queueable;

    public $timeout = 900;

    public $tries = 1;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Artisan::call('fetch:clasher');
    }
}
