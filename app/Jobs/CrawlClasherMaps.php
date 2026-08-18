<?php

namespace App\Jobs;

use App\Services\MapCrawlerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CrawlClasherMaps implements ShouldQueue
{
    use Queueable;

    public $timeout = 1800;

    public $tries = 1;

    /**
     * Execute the job.
     */
    public function handle(MapCrawlerService $crawler): void
    {
        $crawler->crawlAll();
    }
}
