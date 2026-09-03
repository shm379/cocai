<?php

use App\Services\BaseClone\BuildingCaps;

it('applies only to the home village between TH9 and TH17', function () {
    expect(BuildingCaps::applies(15))->toBeTrue()
        ->and(BuildingCaps::applies(9))->toBeTrue()
        ->and(BuildingCaps::applies(17))->toBeTrue()
        ->and(BuildingCaps::applies(8))->toBeFalse()
        ->and(BuildingCaps::applies(18))->toBeFalse()
        ->and(BuildingCaps::applies(null))->toBeFalse()
        ->and(BuildingCaps::applies(15, 'builder'))->toBeFalse();
});

it('returns the wiki caps per type and town hall', function () {
    expect(BuildingCaps::max('cannon', 9))->toBe(5)
        ->and(BuildingCaps::max('cannon', 15))->toBe(7)
        ->and(BuildingCaps::max('archer_tower', 17))->toBe(9)
        ->and(BuildingCaps::max('ricochet_cannon', 15))->toBe(0)
        ->and(BuildingCaps::max('ricochet_cannon', 16))->toBe(2)
        ->and(BuildingCaps::max('hidden_tesla', 11))->toBe(4)
        ->and(BuildingCaps::max('hidden_tesla', 12))->toBe(5)
        ->and(BuildingCaps::max('scattershot', 12))->toBe(0)
        ->and(BuildingCaps::max('monolith', 15))->toBe(1)
        ->and(BuildingCaps::max('builder_hut', 9))->toBe(5)
        ->and(BuildingCaps::max('builder_hut', 10))->toBe(6)
        ->and(BuildingCaps::max('pet_house', 13))->toBe(0)
        ->and(BuildingCaps::max('pet_house', 14))->toBe(1)
        ->and(BuildingCaps::max('gold_mine', 13))->toBe(7)
        ->and(BuildingCaps::max('cannon', 8))->toBeNull()
        ->and(BuildingCaps::max('unknown_type', 15))->toBeNull();
});

it('exposes wall caps, totals and merge groups', function () {
    expect(BuildingCaps::wallCap(9))->toBe(250)
        ->and(BuildingCaps::wallCap(13))->toBe(325)
        ->and(BuildingCaps::wallCap(8))->toBeNull()
        ->and(BuildingCaps::total(15))->toBe(98)
        ->and(BuildingCaps::total(17))->toBe(93)
        ->and(BuildingCaps::groups(8))->toBe([]);

    $groups = BuildingCaps::groups(17);
    expect($groups['cannon_group']['cap'])->toBe(7)
        ->and($groups['cannon_group']['weights']['ricochet_cannon'])->toBe(2)
        ->and($groups['archer_group']['cap'])->toBe(9)
        ->and(BuildingCaps::groups(15)['archer_group']['cap'])->toBe(8);
});
