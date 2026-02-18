<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScheduledMessageStatus;
use Database\Factories\ScheduledMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduledMessage extends Model
{
    /** @use HasFactory<ScheduledMessageFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'provider_connection_id',
        'contact_id',
        'contact_name',
        'message',
        'scheduled_at',
        'status',
        'attempts',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => ScheduledMessageStatus::class,
            'attempts' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ProviderConnection, $this> */
    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class);
    }

    /** @return HasMany<MessageLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    /** @param Builder<ScheduledMessage> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', ScheduledMessageStatus::PENDING->value);
    }

    /** @param Builder<ScheduledMessage> $query */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', ScheduledMessageStatus::PENDING->value)
            ->where('scheduled_at', '<=', now())
            ->where('attempts', '<', 3);
    }
}
