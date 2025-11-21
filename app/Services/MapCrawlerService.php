<?php

namespace App\Services;

use App\Models\Map;
use Goutte\Client;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;

class MapCrawlerService
{
    protected $baseUrl = 'https://example.com'; // آدرس پایه سایت مورد نظر

    public function crawlMaps()
    {
        $client = new Client();

        // برای مثال، URL صفحه اول مربوط به نقشه‌ها را وارد می‌کنیم
        $mapsUrl = 'https://example.com/maps'; // آدرس صفحه نقشه‌ها

        // ارسال درخواست به صفحه نقشه‌ها
        $crawler = $client->request('GET', $mapsUrl);
        
        // استخراج نقشه‌ها
        $maps = $crawler->filter('div.col-auto.post-media')->each(function (Crawler $node) use ($client) {
            // استخراج لینک صفحه جزئیات نقشه
            $mapUrl = $node->filter('a')->attr('href');
            $title = $node->filter('img.map')->attr('title');

            // ورود به صفحه جزئیات نقشه و استخراج اطلاعات
            return $this->crawlMapDetails($client, $this->baseUrl . $mapUrl, $title);
        });

        // بازگشت نقشه‌های استخراج شده
        return $maps;
    }

    private function crawlMapDetails($client, $url, $title)
    {
        // ارسال درخواست به صفحه جزئیات نقشه
        $response = $client->request('GET', $url);
        $html = $response->getBody()->getContents();

        // بارگذاری محتوای HTML
        $crawler = new Crawler($html);

        // استخراج لینک تصویر نقشه
        $imageUrl = $crawler->filter('img.map-pic')->count() > 0
            ? $crawler->filter('img.map-pic')->attr('src')
            : null;

        // استخراج لینک نهایی نقشه
        $copyLinkScript = $crawler->filter('script')->reduce(function (Crawler $node) {
            return strpos($node->text(), 'var copy_link') !== false;
        });

        preg_match('/var copy_link="(.*?)"/', $copyLinkScript->text(), $matches);
        $copyLink = $matches[1] ?? null;

        if ($copyLink && $imageUrl) {
            $finalLink = $this->getFinalLink($this->baseUrl . $copyLink);

            // دانلود تصویر و ذخیره در سرور
            $savedImagePath = $this->downloadAndSaveImage($imageUrl);

            // ذخیره اطلاعات در دیتابیس
            Map::create([
                'title' => $title,
                'image_path' => $savedImagePath,
                'map_link' => $finalLink,
            ]);
        }
    }

    private function downloadAndSaveImage($imageUrl)
    {
        $client = new Client();
        $response = $client->get($imageUrl);

        if ($response->getStatusCode() === 200) {
            $imageName = basename($imageUrl); // نام فایل از URL
            $imagePath = 'maps/' . $imageName; // مسیر ذخیره در فولدر "maps"

            // ذخیره تصویر در Storage
            Storage::disk('public')->put($imagePath, $response->getBody());

            return $imagePath; // مسیر ذخیره‌شده
        }

        return null; // اگر دانلود موفق نبود
    }

    private function getFinalLink($link)
    {
        // تابعی برای هر تغییرات نهایی در لینک مورد نظر
        return $link; // برای مثال، تغییرات نهایی ممکن است اعمال شود
    }
}

