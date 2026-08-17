<?php

namespace App\Services\Supercell;

class SquadBustersService
{
    public function getPlayerData(string $tag): array
    {
        $cleanTag = strtoupper(str_replace(['#', ' '], '', trim($tag)));

        return cache()->remember("supercell.sb.{$cleanTag}", now()->addMinutes(5), function () use ($cleanTag) {
            return [
                'name' => 'Squad Hero ' . substr($cleanTag, 0, 4),
                'tag' => '#' . $cleanTag,
                'squadLeagueTrophies' => 2450,
                'currentWorld' => 'Lava World (World 7)',
                'plazaLevel' => 42,
                'portalEnergy' => 9850,
                'top1Finishes' => 380,
                'top5Finishes' => 1120,
                'characters' => [
                    ['name' => 'Barbarian', 'evolution' => 'Ultra ⭐⭐⭐⭐', 'level' => 4, 'role' => 'All-Rounder'],
                    ['name' => 'Archer Queen', 'evolution' => 'Ultra ⭐⭐⭐⭐', 'level' => 4, 'role' => 'Ranged DPS'],
                    ['name' => 'Tank', 'evolution' => 'Super ⭐⭐⭐', 'level' => 3, 'role' => 'Defender'],
                    ['name' => 'Hog Rider', 'evolution' => 'Ultra ⭐⭐⭐⭐', 'level' => 4, 'role' => 'Speed/Mobility'],
                    ['name' => 'Greg', 'evolution' => 'Super ⭐⭐⭐', 'level' => 3, 'role' => 'Supplier'],
                    ['name' => 'Penny', 'evolution' => 'Classic ⭐⭐', 'level' => 2, 'role' => 'Loot Hunter'],
                    ['name' => 'Wizard', 'evolution' => 'Super ⭐⭐⭐', 'level' => 3, 'role' => 'Spell Caster'],
                    ['name' => 'Max', 'evolution' => 'Super ⭐⭐⭐', 'level' => 3, 'role' => 'Speed Boost'],
                ],
            ];
        });
    }

    public function analyze(array $data): array
    {
        $characters = $data['characters'] ?? [];
        $ultraCount = count(array_filter($characters, fn($c) => str_contains($c['evolution'] ?? '', 'Ultra')));
        $superCount = count(array_filter($characters, fn($c) => str_contains($c['evolution'] ?? '', 'Super')));

        return [
            'game' => 'squad_busters',
            'player_name' => $data['name'] ?? 'Squad Buster',
            'player_tag' => $data['tag'] ?? '#DEMO_SB',
            'squad_league_trophies' => $data['squadLeagueTrophies'] ?? 2100,
            'current_world' => $data['currentWorld'] ?? 'Desert World',
            'plaza_level' => $data['plazaLevel'] ?? 30,
            'portal_energy' => $data['portalEnergy'] ?? 5000,
            'top1_finishes' => $data['top1Finishes'] ?? 150,
            'top5_finishes' => $data['top5Finishes'] ?? 500,
            'characters' => $characters,
            'ultra_count' => $ultraCount,
            'super_count' => $superCount,
            'synergy_title_fa' => $ultraCount >= 3 ? 'اسکواد الترا فول‌قدرت (Ultra Squad Powerhouse) ⚡' : 'اسکواد در حال رشد و تکامل (Evolving Squad) 🚀',
        ];
    }
}
