<?php

declare(strict_types=1);

use App\Models\User;
use Src\Channel\Models\Channel;

describe('GET /v1/channels', function () {

    it('returns only active channels by default', function () {
        $this->actingAs(User::factory()->create())
            ->getJson(route('channels.index'))
            ->assertOk()
            ->assertJsonMissing(['slug' => 'whatsapp']);
    });

    it('returns channels sorted alphabetically', function () {
        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('channels.index'))
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name')->all();

        expect($names)->toBe(collect($names)->sort()->values()->all());
    });

    it('exposes id, name and slug fields only', function () {
        $this->actingAs(User::factory()->create())
            ->getJson(route('channels.index'))
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'slug']]])
            ->assertJsonMissingPath('data.0.active')
            ->assertJsonMissingPath('data.0.created_at')
            ->assertJsonMissingPath('data.0.updated_at');
    });

    it('filters channels by name (case-insensitive, partial match)', function () {
        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('channels.index').'?name=whatsapp')
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name')->all();

        expect($names)->each(fn ($name) => $name->toContain('Whatsapp'));
    });

    it('returns empty data when name filter matches nothing', function () {
        $this->actingAs(User::factory()->create())
            ->getJson(route('channels.index').'?name=nonexistent')
            ->assertOk()
            ->assertJsonPath('data', []);
    });

    it('returns only inactive channels when status is inactive', function () {
        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('channels.index').'?status=inactive')
            ->assertOk();

        $slugs = collect($response->json('data'))->pluck('slug')->all();

        expect($slugs)->toBe(['whatsapp']);
    });

    it('returns all channels when status is all', function () {
        $total = Channel::query()->count();
        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('channels.index').'?status=all')
            ->assertOk();

        expect($response->json('data'))->toHaveCount($total);
    });

    it('combines name and status filters', function () {
        $this->actingAs(User::factory()->create())
            ->getJson(route('channels.index').'?name=whatsapp&status=all')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('returns 422 when status is invalid', function () {
        $this->actingAs(User::factory()->create())
            ->getJson(route('channels.index').'?status=unknown')
            ->assertUnprocessable();
    });

    it('returns 422 when name exceeds 255 characters', function () {
        $this->actingAs(User::factory()->create())
            ->getJson(route('channels.index').'?name='.str_repeat('a', 256))
            ->assertUnprocessable();
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson(route('channels.index'))
            ->assertUnauthorized();
    });

});
