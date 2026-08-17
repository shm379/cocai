<?php

namespace App\Services\Supercell;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClashRoyaleService
{
    protected ?string $apiToken;
    protected string $apiBase = 'https://api.clashroyale.com/v1/players/%23';

    public function __construct()
    {
        $this->apiToken = config('services.clash.api_token');
    }

    public function getPlayerData(string $tag): array
    {
        $cleanTag = $this->normalizeTag($tag);

        if (in_array($cleanTag, ['DEMO', 'TEST', 'CR1', 'CR2'])) {
            return $this->generateDemoProfile($cleanTag);
        }

        return cache()->remember("supercell.cr.{$cleanTag}", now()->addMinutes(5), function () use ($cleanTag) {
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
                    Log::warning('Clash Royale API request failed: ' . $e->getMessage());
                }
            }

            return $this->generateDemoProfile($cleanTag);
        });
    }

    public function analyze(array $data): array
    {
        $trophies = (int) ($data['trophies'] ?? 0);
        $bestTrophies = (int) ($data['bestTrophies'] ?? 0);
        $arena = $data['arena']['name'] ?? 'Arena 15 (Miner\'s Mine)';
        $cards = $data['cards'] ?? [];
        $deck = $data['currentDeck'] ?? [];
        $clan = $data['clan'] ?? null;
        $wins = (int) ($data['wins'] ?? 0);
        $threeCrownWins = (int) ($data['threeCrownWins'] ?? 0);

        // میانگین اکسیر دک فعلی
        $avgElixir = 0;
        if (! empty($deck)) {
            $totalElixir = array_sum(array_map(fn($c) => (float) ($c['elixirCost'] ?? 3.5), $deck));
            $avgElixir = round($totalElixir / count($deck), 1);
        }

        // کارت‌های لول مکس (لول ۱۴ یا ۱۵)
        $maxedCardsCount = count(array_filter($cards, fn($c) => ($c['level'] ?? 0) >= 14));

        return [
            'game' => 'clash_royale',
            'player_name' => $data['name'] ?? 'Royal Champion',
            'player_tag' => $data['tag'] ?? '#DEMO_CR',
            'trophies' => $trophies,
            'best_trophies' => $bestTrophies,
            'arena' => $arena,
            'exp_level' => $data['expLevel'] ?? 50,
            'wins' => $wins,
            'three_crown_wins' => $threeCrownWins,
            'clan' => $clan,
            'current_deck' => $deck,
            'avg_elixir' => $avgElixir,
            'total_cards' => count($cards),
            'maxed_cards_count' => $maxedCardsCount,
            'league_title_fa' => $this->getLeagueTitleFa($trophies),
            'deck_synergy_fa' => $avgElixir <= 3.3 ? 'دک سرعتی و چرخش سریع (Fast Cycle) ⚡' : ($avgElixir >= 4.0 ? 'دک سنگین و بیت‌داون (Beatdown Push) 🐘' : 'دک کنترل و تعادل اکسیر (Control Deck) ⚖️'),
        ];
    }

    private function getLeagueTitleFa(int $trophies): string
    {
        return match (true) {
            $trophies >= 9000 => 'لیگ قهرمانان نهایی (Ultimate Champion) 🏆',
            $trophies >= 8000 => 'قهرمان بزرگ (Grand Champion) 👑',
            $trophies >= 7000 => 'قهرمان رویال (Royal Champion) ⭐',
            $trophies >= 6000 => 'مستر لیگ (Master League) ⚔️',
            default => 'مسیر پیروزی و آرناها (Trophy Road) 🛡️',
        };
    }

    private function generateDemoProfile(string $tag): array
    {
        return [
            'name' => 'Royal King ' . substr($tag, 0, 4),
            'tag' => '#' . $tag,
            'expLevel' => 54,
            'trophies' => 7850,
            'bestTrophies' => 8200,
            'wins' => 4520,
            'threeCrownWins' => 1890,
            'arena' => ['name' => 'Dragon Spa (Arena 21)'],
            'clan' => [
                'name' => 'Persian Royals',
                'tag' => '#22ROYAL',
                'badgeId' => 16000045,
            ],
            'currentDeck' => [
                ['name' => 'Hog Rider', 'level' => 15, 'maxLevel' => 15, 'elixirCost' => 4, 'iconUrls' => ['medium' => 'https://api-assets.clashroyale.com/cards/300/Ubu0o1ZZ995xGPRa8V40etSR361TNxK13061A2U9Pio.png']],
                ['name' => 'Firecracker', 'level' => 15, 'maxLevel' => 15, 'elixirCost' => 3, 'iconUrls' => ['medium' => 'https://api-assets.clashroyale.com/cards/300/C1epMGdHkV4yqU81a5Tdd6b_M8uJ51kH6G-q_pM23qE.png']],
                ['name' => 'Log', 'level' => 15, 'maxLevel' => 15, 'elixirCost' => 2, 'iconUrls' => ['medium' => 'https://api-assets.clashroyale.com/cards/300/26mGKTwdaERe8zgGgNYTr9t3SgsVSbsFLvyGJji5C6o.png']],
                ['name' => 'Earthquake', 'level' => 14, 'maxLevel' => 15, 'elixirCost' => 3, 'iconUrls' => ['medium' => 'https://api-assets.clashroyale.com/cards/300/XeTtRsq8kbgOMqnZrWbJ2S1r5Qy_qf6V8qS7bM-y9uI.png']],
                ['name' => 'Skeletons', 'level' => 14, 'maxLevel' => 15, 'elixirCost' => 1, 'iconUrls' => ['medium' => 'https://api-assets.clashroyale.com/cards/300/oO7iog5rEIpf160bZv4hyubU46YnV3DY82U3CJ64nyw.png']],
                ['name' => 'Ice Spirit', 'level' => 14, 'maxLevel' => 15, 'elixirCost' => 1, 'iconUrls' => ['medium' => 'https://api-assets.clashroyale.com/cards/300/LV1AHiaTHiIHooJ2NkWenexlJ5Te6He85iqU85KFZ3g.png']],
                ['name' => 'Tesla', 'level' => 15, 'maxLevel' => 15, 'elixirCost' => 4, 'iconUrls' => ['medium' => 'https://api-assets.clashroyale.com/cards/300/OiwnA3Y0A3Z_Q9r8kQv1q7W5r7W9W3N6Q8T5N2K1.png']],
                ['name' => 'Knight', 'level' => 15, 'maxLevel' => 15, 'elixirCost' => 3, 'iconUrls' => ['medium' => 'https://api-assets.clashroyale.com/cards/300/jAj1Q5rclXxU9kVImq5ZQKq5r7W9W3N6Q8T5N2K1.png']],
            ],
            'cards' => array_fill(0, 110, ['level' => 14]),
        ];
    }

    private function normalizeTag(string $tag): string
    {
        $tag = strtoupper(str_replace(['#', ' '], '', trim($tag)));
        return str_replace('O', '0', $tag);
    }
}
