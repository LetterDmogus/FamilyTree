<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyEvent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'event_date',
        'event_time',
        'location',
        'status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'location' => 'json',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }
}
