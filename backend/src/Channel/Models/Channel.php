<?php

declare(strict_types=1);

namespace Src\Channel\Models;

use Illuminate\Database\Eloquent\Model;

final class Channel extends Model
{
    public $incrementing = false;

    protected $connection = 'landlord';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'active' => 'boolean',
        ];
    }
}
