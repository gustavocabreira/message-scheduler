<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Src\Tenant\Models\Tenant;

#[Fillable(['name', 'email', 'password', 'huggy_id', 'huggy_access_token', 'huggy_refresh_token', 'huggy_token_expires_at', 'avatar_path', 'last_workspace_id'])]
#[Hidden(['password', 'remember_token', 'huggy_access_token', 'huggy_refresh_token'])]
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'huggy_access_token' => 'encrypted',
            'huggy_refresh_token' => 'encrypted',
            'huggy_token_expires_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<Tenant, $this, \Illuminate\Database\Eloquent\Relations\Pivot, string> */
    public function tenants(): BelongsToMany
    {
        $relation = $this->belongsToMany(
            related: Tenant::class,
            table: 'tenant_user',
            foreignPivotKey: 'user_id',
            relatedPivotKey: 'tenant_id',
        );

        // @phpstan-ignore-next-line method.notFound
        return $relation->withoutTimestamps();
    }
}
