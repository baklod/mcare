<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialDocumentDownload extends Model
{
    use HasFactory;

    protected $fillable = [
        'official_document_id',
        'user_id',
        'actor_role',
        'ip_address',
        'user_agent',
        'downloaded_at',
    ];

    protected function casts(): array
    {
        return ['downloaded_at' => 'datetime'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(OfficialDocument::class, 'official_document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
