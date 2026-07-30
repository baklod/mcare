<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymongoWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_type',
        'resource_id',
        'livemode',
        'payload_sha256',
        'status',
        'error_code',
        'received_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'livemode' => 'boolean',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
