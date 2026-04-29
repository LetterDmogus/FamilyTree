<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAttachment extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'type',
        'file_path',
        'content',
        'is_private',
        'metadata',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'metadata' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
