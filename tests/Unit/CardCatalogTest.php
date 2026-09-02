<?php

use App\Services\BaseClone\CardCatalog;

it('loads the bundled Clash Royale card catalog with official ids', function () {
    $catalog = new CardCatalog;

    expect($catalog->count())->toBeGreaterThan(100)
        ->and($catalog->find('Hog Rider')['card']['id'])->toBe(26000021)
        ->and($catalog->find('The Log')['card']['id'])->toBe(28000011)
        ->and($catalog->find('Cannon')['card']['type'])->toBe('Building');
});

it('normalizes punctuation, aliases and evolution prefixes', function () {
    $catalog = new CardCatalog;

    expect($catalog->find('PEKKA')['card']['key'])->toBe('pekka')
        ->and($catalog->find('mini pekka')['card']['key'])->toBe('mini-pekka')
        ->and($catalog->find('x bow')['card']['key'])->toBe('x-bow')
        ->and($catalog->find('Log')['card']['key'])->toBe('the-log')
        ->and($catalog->find('MK')['card']['key'])->toBe('mega-knight')
        ->and($catalog->find('e-wiz')['card']['key'])->toBe('electro-wizard');

    $evo = $catalog->find('Evolved Knight');
    expect($evo['card']['key'])->toBe('knight')
        ->and($evo['evolution'])->toBeTrue();

    expect($catalog->find('Knight')['evolution'])->toBeFalse();
});

it('tolerates small spelling mistakes and rejects garbage', function () {
    $catalog = new CardCatalog;

    expect($catalog->find('Musketer')['card']['key'])->toBe('musketeer')
        ->and($catalog->find('Valkirie')['card']['key'])->toBe('valkyrie')
        ->and($catalog->find('Dragon Statue'))->toBeNull()
        ->and($catalog->find(''))->toBeNull();
});
