<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

/**
 * خواندن دک ۸ کارتی کلش رویال از روی تصویر (اسکرین‌شات بازی یا سایت).
 */
class DeckVisionExtractor extends BaseVisionAnalyzer
{
    /**
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array{ok: bool, data?: array, message?: string, raw_content?: string}
     */
    public function extractDeck($image): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'تنظیمات AI Vision انجام نشده است.'];
        }

        $base64 = $this->imageToBase64($image);
        if ($base64 === null) {
            return ['ok' => false, 'message' => 'خطا در خواندن تصویر.'];
        }

        $response = $this->callVisionModel($base64);
        if ($response === null || empty($response['content'])) {
            return ['ok' => false, 'message' => 'پاسخی از مدل Vision دریافت نشد.'];
        }

        $data = $this->parseDeckJson($response['content']);
        if ($data === null || $data['cards'] === []) {
            return [
                'ok' => false,
                'message' => 'هیچ کارتی در تصویر تشخیص داده نشد. اسکرین‌شات واضحی از ۸ کارت دک بفرستید.',
                'raw_content' => $response['content'],
            ];
        }

        return ['ok' => true, 'data' => $data, 'raw_content' => $response['content']];
    }

    protected function parseDeckJson(string $content): ?array
    {
        $content = trim($content);
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', $content) ?? $content;
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start === false || $end === false || $end <= $start) {
            Log::warning('DeckVisionExtractor: no JSON in response.', ['content' => mb_substr($content, 0, 400)]);

            return null;
        }

        $data = json_decode(substr($content, $start, $end - $start + 1), true);
        if (! is_array($data)) {
            return null;
        }

        $cards = [];
        foreach ((array) ($data['cards'] ?? []) as $i => $c) {
            $name = is_array($c) ? ($c['name'] ?? null) : $c;
            if (! is_string($name) || trim($name) === '') {
                continue;
            }
            $cards[] = [
                'slot' => is_array($c) && isset($c['slot']) && is_numeric($c['slot']) ? (int) $c['slot'] : $i + 1,
                'name' => trim($name),
                'evolution' => is_array($c) && ! empty($c['evolution']),
                'level' => is_array($c) && isset($c['level']) && is_numeric($c['level']) ? (int) $c['level'] : null,
            ];
            if (count($cards) >= 8) {
                break;
            }
        }

        return [
            'cards' => $cards,
            'tower_troop' => is_string($data['tower_troop'] ?? null) ? trim($data['tower_troop']) : null,
            'source' => is_string($data['source'] ?? null) ? $data['source'] : 'unknown',
        ];
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a Clash Royale deck reader. The user provides an image showing a deck: an in-game deck slot, a battle/end screen, a RoyaleAPI/DeckShop page or any picture with card portraits. Return ONLY a JSON object (no markdown, no code fences, no commentary):

{
  "cards": [
    {"slot": 1, "name": "Hog Rider", "evolution": false, "level": 14},
    {"slot": 2, "name": "The Log", "evolution": false}
  ],
  "tower_troop": "Tower Princess",
  "source": "in_game"
}

Rules:
- List the cards of ONE deck only, in reading order (left to right, then top to bottom), at most 8 entries. If two decks are visible (e.g. a battle screen), use the deck at the bottom/left which belongs to the player.
- "name" must be the official English card name (e.g. "P.E.K.K.A", "Mini P.E.K.K.A", "X-Bow", "The Log", "Elite Barbarians", "Electro Wizard").
- "evolution": true only when the card clearly shows the Evolution frame or badge (purple wings / star); otherwise false.
- "level": the card level if readable, otherwise omit it.
- "tower_troop": the tower troop if visible (Tower Princess, Cannoneer, Dagger Duchess, Royal Chef), otherwise null.
- "source": "in_game", "website" or "unknown".
- Never invent cards you cannot see. If unsure between two cards, pick the most likely and keep going.
PROMPT;
    }

    protected function userPrompt(): string
    {
        return 'Read the 8 cards of this Clash Royale deck and return the JSON.';
    }

    protected function maxTokens(): int
    {
        return 800;
    }

    protected function temperature(): float
    {
        return 0.0;
    }
}
