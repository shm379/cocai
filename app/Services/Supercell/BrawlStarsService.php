<?php

namespace App\Services\Supercell;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrawlStarsService
{
    protected ?string $apiToken;
    protected string $apiBase = 'https://api.brawlstars.com/v1/players/%23';

    public function __construct()
    {
        $this->apiToken = config('services.clash.api_token');
    }

    public function getPlayerData(string $tag): array
    {
        $cleanTag = $this->normalizeTag($tag);

        if (in_array($cleanTag, ['DEMO', 'TEST', 'BS1', 'BS2'])) {
            return $this->generateDemoProfile($cleanTag);
        }

        return cache()->remember("supercell.bs.{$cleanTag}", now()->addMinutes(5), function () use ($cleanTag) {
            if (! empty($this->apiToken)) {
                try {
                    $response = Http::timeout(10)
                        ->acceptJson()
                        ->withToken($this->apiToken)
                        ->get($this->apiBase . rawurlencode($cleanTag));

                    if ($response->ok()) {
                        $data = $response->json();
                        if (! empty($data['name'])) {
                            return $data;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Brawl Stars API request failed: ' . $e->getMessage());
                }
            }

            return $this->generateDemoProfile($cleanTag);
        });
    }

    public function analyze(array $data): array
    {
        $trophies = (int) ($data['trophies'] ?? 0);
        $highestTrophies = (int) ($data['highestTrophies'] ?? 0);
        $t3v3Victories = (int) ($data['3vs3Victories'] ?? 0);
        $soloVictories = (int) ($data['soloVictories'] ?? 0);
        $duoVictories = (int) ($data['duoVictories'] ?? 0);
        $brawlers = $data['brawlers'] ?? [];
        $club = $data['club'] ?? null;

        // براولرهای پاور ۱۱ و هایپرشارژدار
        $p11Brawlers = count(array_filter($brawlers, fn($b) => ($b['power'] ?? 0) >= 11));
        $hyperchargedBrawlers = count(array_filter($brawlers, fn($b) => !empty($b['gears'] ?? [])));

        return [
            'game' => 'brawl_stars',
            'player_name' => $data['name'] ?? 'Brawl Star',
            'player_tag' => $data['tag'] ?? '#DEMO_BS',
            'trophies' => $trophies,
            'highest_trophies' => $highestTrophies,
            'exp_level' => $data['expLevel'] ?? 180,
            'v3v3_victories' => $t3v3Victories,
            'solo_victories' => $soloVictories,
            'duo_victories' => $duoVictories,
            'total_brawlers' => count($brawlers),
            'p11_brawlers_count' => $p11Brawlers,
            'hypercharged_count' => $hyperchargedBrawlers,
            'club' => $club,
            'ranked_tier_fa' => $this->getRankedTierFa($trophies),
            'mastery_title_fa' => $t3v3Victories >= 5000 ? 'اسطوره مسابقات تیمی (3v3 Legend) 🔥' : 'جنگجوی میدانی (Showdown Specialist) 🌵',
            'brawlers' => array_slice($brawlers, 0, 12),
        ];
    }

    private function getRankedTierFa(int $trophies): string
    {
        return match (true) {
            $trophies >= 50000 => 'مسترز (Masters Division) 🏆',
            $trophies >= 35000 => 'لجندری (Legendary Tier) 👑',
            $trophies >= 25000 => 'میتیک (Mythic Tier) 🔮',
            $trophies >= 15000 => 'دایموند (Diamond Tier) 💎',
            default => 'گلد و سیلور (Gold Tier) 🛡️',
        };
    }

    private function generateDemoProfile(string $tag): array
    {
        return [
            'name' => 'Brawl Master ' . substr($tag, 0, 4),
            'tag' => '#' . $tag,
            'expLevel' => 195,
            'trophies' => 38400,
            'highestTrophies' => 41200,
            '3vs3Victories' => 8420,
            'soloVictories' => 1250,
            'duoVictories' => 980,
            'club' => [
                'name' => 'Persian Brawlers',
                'tag' => '#22BRAWL',
            ],
            'brawlers' => [
                ['name' => 'Mortis', 'power' => 11, 'rank' => 30, 'trophies' => 1000, 'highestTrophies' => 1050],
                ['name' => 'Edgar', 'power' => 11, 'rank' => 28, 'trophies' => 920, 'highestTrophies' => 950],
                ['name' => 'Fang', 'power' => 11, 'rank' => 27, 'trophies' => 880, 'highestTrophies' => 900],
                ['name' => 'Piper', 'power' => 11, 'rank' => 29, 'trophies' => 960, 'highestTrophies' => 980],
                ['name' => 'Colt', 'power' => 11, 'rank' => 26, 'trophies' => 840, 'highestTrophies' => 860],
                ['name' => 'Leon', 'power' => 11, 'rank' => 28, 'trophies' => 910, 'highestTrophies' => 930],
                ['name' => 'Crow', 'power' => 11, 'rank' => 27, 'trophies' => 870, 'highestTrophies' => 890],
                ['name' => 'Spike', 'power' => 11, 'rank' => 28, 'trophies' => 900, 'highestTrophies' => 920],
            ],
        ];
    }

    private function normalizeTag(string $tag): string
    {
        $tag = strtoupper(str_replace(['#', ' '], '', trim($tag)));
        return str_replace('O', '0', $tag);
    }
}
