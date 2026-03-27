<?php

declare(strict_types=1);

use App\Models\User;

describe('Logout', function () {

    it('returns success on logout', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('auth.logout'))
            ->assertOk()
            ->assertJson(['message' => 'Logged out successfully']);
    });

    it('returns 401 when called without a session', function () {
        $this->postJson(route('auth.logout'))
            ->assertUnauthorized();
    });

});
