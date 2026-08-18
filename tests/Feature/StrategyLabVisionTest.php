<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Http::preventStrayRequests();
    Storage::fake('public');
});

it('detects buildings from uploaded image using AI vision', function () {
    $user = User::factory()->create();

    Http::fake([
        '*/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'buildings' => [
                                ['type' => 'town_hall', 'x' => 50, 'y' => 50],
                                ['type' => 'air_defense', 'x' => 30, 'y' => 30],
                            ],
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $image = UploadedFile::fake()->image('base.jpg', 200, 200);

    $response = $this->actingAs($user)
        ->postJson('/api/strategy-lab/detect-vision', [
            'image' => $image,
        ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('buildings.0.type', 'town_hall')
        ->assertJsonPath('buildings.1.type', 'air_defense')
        ->assertJsonPath('analysis.ok', true);
});

it('returns error when AI vision is not configured', function () {
    config()->set('services.nabu.api_key', null);

    $user = User::factory()->create();
    $image = UploadedFile::fake()->image('base.jpg', 200, 200);

    $response = $this->actingAs($user)
        ->postJson('/api/strategy-lab/detect-vision', [
            'image' => $image,
        ]);

    $response->assertStatus(503)
        ->assertJsonPath('ok', false);
});
