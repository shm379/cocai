<?php

namespace App\Services\Supercell;

class BoomBeachService
{
    public function getPlayerData(string $tag): array
    {
        $cleanTag = strtoupper(str_replace(['#', ' '], '', trim($tag)));

        return cache()->remember("supercell.bb.{$cleanTag}", now()->addMinutes(5), function () use ($cleanTag) {
            return [
                'name' => 'Commander ' . substr($cleanTag, 0, 4),
                'tag' => '#' . $cleanTag,
                'hqLevel' => 26,
                'expLevel' => 76,
                'victoryPoints' => 1180,
                'taskForce' => [
                    'name' => 'Persian Commandos',
                    'tag' => '#22TASK',
                    'forcePoints' => 34200,
                ],
                'gunboatLevel' => 24,
                'radarLevel' => 22,
                'statues' => [
                    ['type' => 'Magma (Troop Damage)', 'bonus' => '+35%'],
                    ['type' => 'Magma (Troop Health)', 'bonus' => '+34%'],
                    ['type' => 'Dark (Gunboat Energy)', 'bonus' => '+42%'],
                    ['type' => 'Dark (Resource Reward)', 'bonus' => '+50%'],
                    ['type' => 'Ice (Building Health)', 'bonus' => '+32%'],
                ],
                'heroes' => [
                    ['name' => 'Pvt. Bullit', 'level' => 8, 'ability' => 'Energy Drink'],
                    ['name' => 'Sgt. Brick', 'level' => 8, 'ability' => 'Battle Orders'],
                    ['name' => 'Capt. Everspark', 'level' => 8, 'ability' => 'Critter Swarm'],
                    ['name' => 'Dr. Kavan', 'level' => 8, 'ability' => 'Second Wind'],
                ],
            ];
        });
    }

    public function analyze(array $data): array
    {
        $hq = (int) ($data['hqLevel'] ?? 1);
        $vp = (int) ($data['victoryPoints'] ?? 0);

        return [
            'game' => 'boom_beach',
            'player_name' => $data['name'] ?? 'Commander',
            'player_tag' => $data['tag'] ?? '#DEMO_BB',
            'hq_level' => $hq,
            'exp_level' => $data['expLevel'] ?? 50,
            'victory_points' => $vp,
            'task_force' => $data['taskForce'] ?? null,
            'gunboat_level' => $data['gunboatLevel'] ?? 20,
            'statues' => $data['statues'] ?? [],
            'heroes' => $data['heroes'] ?? [],
            'rank_title_fa' => $vp >= 1000 ? 'فرمانده ارشد مجمع‌الجزایر (High Admiral) 🎖️' : ($vp >= 600 ? 'کاپیتان رزمی (Battle Captain) ⚓' : 'فرمانده خط مقدم (Frontline Commander) 🏝️'),
        ];
    }
}
