<?php

declare(strict_types=1);

use App\Models\ProviderConnection;
use App\Providers\MessageProviders\HuggyProvider;
use Illuminate\Support\Facades\Http;

describe('HuggyProvider', function (): void {
    beforeEach(function (): void {
        config()->set('services.huggy.api_url', 'https://api.huggy.app');
    });

    it('testConnection returns true when API responds 200', function (): void {
        Http::fake(['*/v3/me' => Http::response(['id' => 1], 200)]);

        $connection = ProviderConnection::factory()->active()->make();
        $provider = new HuggyProvider($connection);

        expect($provider->testConnection())->toBeTrue();
    });

    it('testConnection returns false when API responds 401', function (): void {
        Http::fake(['*/v3/me' => Http::response([], 401)]);

        $connection = ProviderConnection::factory()->active()->make();
        $provider = new HuggyProvider($connection);

        expect($provider->testConnection())->toBeFalse();
    });

    it('testConnection returns false when no access_token in credentials', function (): void {
        $connection = ProviderConnection::factory()->make([
            'credentials' => json_encode([]),
        ]);
        $provider = new HuggyProvider($connection);

        expect($provider->testConnection())->toBeFalse();
    });

    it('getContacts returns array of contacts', function (): void {
        Http::fake([
            '*/v3/contacts*' => Http::response([
                'data' => [
                    ['id' => '1', 'name' => 'Alice'],
                    ['id' => '2', 'name' => 'Bob'],
                ],
            ], 200),
        ]);

        $connection = ProviderConnection::factory()->active()->make();
        $provider = new HuggyProvider($connection);

        $contacts = $provider->getContacts();

        expect($contacts)->toHaveCount(2);
    });

    it('getContacts returns empty array on API failure', function (): void {
        Http::fake(['*/v3/contacts*' => Http::response([], 500)]);

        $connection = ProviderConnection::factory()->active()->make();
        $provider = new HuggyProvider($connection);

        expect($provider->getContacts())->toBeEmpty();
    });

    it('sendMessage returns true on success', function (): void {
        Http::fake(['*/v3/contacts/*/messages' => Http::response([], 200)]);

        $connection = ProviderConnection::factory()->active()->make();
        $provider = new HuggyProvider($connection);

        expect($provider->sendMessage('contact-123', 'Hello!'))->toBeTrue();
    });

    it('sendMessage returns false on failure', function (): void {
        Http::fake(['*/v3/contacts/*/messages' => Http::response([], 500)]);

        $connection = ProviderConnection::factory()->active()->make();
        $provider = new HuggyProvider($connection);

        expect($provider->sendMessage('contact-123', 'Hello!'))->toBeFalse();
    });
});
