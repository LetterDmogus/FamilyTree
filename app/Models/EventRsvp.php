<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRsvp extends Model
{
    protected $fillable = [
        'family_event_id',
        'user_id',
        'status',
        'headcount',
        'notes',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(FamilyEvent::class, 'family_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
