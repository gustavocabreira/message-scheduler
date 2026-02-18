<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MessageLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    /** @use HasFactory<MessageLogFactory> */
    use HasFactory;

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'scheduled_message_id',
        'attempt',
        'status',
        'response',
        'error_message',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'attempt' => 'integer',
        ];
    }

    /** @return BelongsTo<ScheduledMessage, $this> */
    public function scheduledMessage(): BelongsTo
    {
        return $this->belongsTo(ScheduledMessage::class);
    }
}
