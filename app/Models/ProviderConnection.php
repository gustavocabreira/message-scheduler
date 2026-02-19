<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProviderStatus;
use App\Enums\ProviderType;
use Database\Factories\ProviderConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ProviderConnection extends Model
{
    /** @use HasFactory<ProviderConnectionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'provider_type',
        'status',
        'credentials',
        'settings',
        'connected_at',
        'last_synced_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'credentials',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'status' => ProviderStatus::class,
            'provider_type' => ProviderType::class,
            'connected_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setCredentialsAttribute(string $value): void
    {
        $this->attributes['credentials'] = Crypt::encrypt($value);
    }

    public function getCredentialsAttribute(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        /** @var string */
        return Crypt::decrypt($value);
    }

    /** @return array<string, mixed> */
    public function getDecryptedCredentials(): array
    {
        $raw = $this->getCredentialsAttribute($this->attributes['credentials'] ?? null);

        if ($raw === null) {
            return [];
        }

        /** @var array<string, mixed> */
        return json_decode($raw, true) ?? [];
    }
}
