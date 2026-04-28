<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'gender',
        'is_alive',
        'death_date',
        'birth_date',
        'birth_place',
        'death_place',
        'is_family_head',
        'additional_info',
        'social_media',
        'profile_photo_path',
    ];

    protected $casts = [
        'is_alive' => 'boolean',
        'death_date' => 'date',
        'birth_date' => 'date',
        'birth_place' => 'array',
        'death_place' => 'array',
        'is_family_head' => 'boolean',
        'additional_info' => 'array',
        'social_media' => 'array',
    ];

    protected $appends = ['photo_url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_path
            ? Storage::disk('public')->url($this->profile_photo_path)
            : null;
    }
}
