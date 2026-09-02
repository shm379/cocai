<?php

namespace App\Services\BaseClone\Games;

use App\Services\AI\DeckVisionExtractor;
use App\Services\BaseClone\CardCatalog;
use Illuminate\Http\UploadedFile;

/**
 * موتور کلش رویال: عکس دک → ۸ کارت → لینک رسمی «کپی دک» که مستقیم در بازی باز می‌شود.
 *
 * برخلاف کلش آف کلنز، لینک دک کلش رویال فقط شناسهٔ کارت‌ها را حمل می‌کند و
 * بنابراین از روی تصویر به‌طور کامل قابل ساخت است.
 */
class ClashRoyaleDeckAdapter implements GameAdapter
{
    public const DECK_SIZE = 8;

    public function __construct(
        protected DeckVisionExtractor $vision,
        protected CardCatalog $catalog,
    ) {
    }

    public function key(): string
    {
        return 'clash_royale';
    }

    public function label(): string
    {
        return 'کلش رویال — دک';
    }

    public function meta(): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'short' => 'کلش رویال',
            'game' => 'clash_royale',
            'icon' => '👑',
            'color' => 'blue',
            'result_type' => 'deck',
            'hint' => 'اسکرین‌شات دک (داخل بازی، صفحهٔ نتیجهٔ بتل یا سایت). ۸ کارت تشخیص داده می‌شود و لینک رسمی کپی دک ساخته می‌شود که مستقیم در بازی باز می‌شود.',
            'placeholder' => 'عکس دک ۸ کارتی',
        ];
    }

    public function isConfigured(): bool
    {
        return $this->vision->isConfigured() && $this->catalog->count() > 0;
    }

    public function analyze(UploadedFile $image, ?string $hash): array
    {
        $extracted = $this->vision->extractDeck($image);
        if (! ($extracted['ok'] ?? false)) {
            return ['ok' => false, 'message' => $extracted['message'] ?? 'خطا در خواندن دک.', 'reason' => $extracted['reason'] ?? 'parse', 'matches' => []];
        }

        $cards = [];
        $unresolved = [];
        $unverified = [];
        $ids = [];
        $slots = [];
        $elixirTotal = 0;

        foreach ($extracted['data']['cards'] as $raw) {
            $match = $this->catalog->find($raw['name']);
            if ($match === null) {
                $unresolved[] = $raw['name'];
                continue;
            }

            $card = $match['card'];
            $evolution = $raw['evolution'] || $match['evolution'];

            $entry = [
                'slot' => $raw['slot'],
                'id' => (int) $card['id'],
                'key' => $card['key'],
                'name' => $card['name'],
                'elixir' => (int) $card['elixir'],
                'rarity' => $card['rarity'],
                'type' => $card['type'],
                'evolution' => $evolution,
                'verified' => (bool) ($card['verified'] ?? true),
                'detected_as' => $raw['name'],
            ];
            if ($raw['level'] !== null) {
                $entry['level'] = $raw['level'];
            }

            $cards[] = $entry;
            $ids[] = (int) $card['id'];
            $slots[] = $evolution ? 1 : 0;
            $elixirTotal += (int) $card['elixir'];
            if (! $entry['verified']) {
                $unverified[] = $card['name'];
            }
        }

        if ($cards === []) {
            return [
                'ok' => false,
                'message' => 'کارت‌های تصویر با کاتالوگ کلش رویال تطبیق داده نشد: '.implode('، ', array_slice($unresolved, 0, 8)),
                'matches' => [],
            ];
        }

        $towerTroop = null;
        if (! empty($extracted['data']['tower_troop'])) {
            $tt = $this->catalog->find($extracted['data']['tower_troop']);
            if ($tt && ($tt['card']['type'] ?? '') === 'Tower Troop') {
                $towerTroop = ['id' => (int) $tt['card']['id'], 'name' => $tt['card']['name'], 'verified' => (bool) ($tt['card']['verified'] ?? true)];
            }
        }

        $complete = count($cards) === self::DECK_SIZE && $unresolved === [];
        $hasEvolution = in_array(1, $slots, true);

        $copyLink = $complete ? self::classicLink($ids) : null;
        $copyLinkEvo = ($complete && ($hasEvolution || $towerTroop)) ? self::copyDeckLink($ids, $slots, $towerTroop['id'] ?? null) : null;

        $layout = [
            'type' => 'deck',
            'cards' => $cards,
            'unresolved' => $unresolved,
            'unverified' => $unverified,
            'complete' => $complete,
            'avg_elixir' => round($elixirTotal / count($cards), 1),
            'tower_troop' => $towerTroop,
            'copy_link_evo' => $copyLinkEvo,
            'source' => $extracted['data']['source'] ?? 'unknown',
            'stats' => [
                'card_count' => count($cards),
                'unresolved_count' => count($unresolved),
                'evolution_count' => array_sum($slots),
            ],
        ];

        return [
            'ok' => true,
            'layout' => $layout,
            'copy_link' => $copyLink,
            'th_level' => null,
            'matched_map_id' => null,
            'match_distance' => null,
            'matches' => [],
        ];
    }

    /**
     * لینک کلاسیک کپی دک (بدون اطلاعات Evolution).
     *
     * @param  array<int, int>  $ids
     */
    public static function classicLink(array $ids): string
    {
        return 'https://link.clashroyale.com/deck/en?deck='.implode(';', $ids).'&l=Royals';
    }

    /**
     * لینک جدید بازی با اسلات‌های Evolution و تاور تروپ.
     *
     * @param  array<int, int>  $ids
     * @param  array<int, int>  $slots
     */
    public static function copyDeckLink(array $ids, array $slots, ?int $towerTroopId = null): string
    {
        $url = 'https://link.clashroyale.com/en/?clashroyale://copyDeck?deck='.implode(';', $ids)
            .'&slots='.implode(';', $slots);
        if ($towerTroopId) {
            $url .= '&tt='.$towerTroopId;
        }

        return $url.'&l=Royals';
    }
}
