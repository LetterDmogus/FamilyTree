<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function children()
    {
        return $this->belongsToMany(User::class, 'relations', 'user_id', 'related_user_id')
            ->wherePivot('type', 'child')
            ->withPivot('is_blood')
            ->withTimestamps();
    }

    public function spouse()
    {
        return $this->belongsToMany(User::class, 'relations', 'user_id', 'related_user_id')
            ->wherePivot('type', 'spouse')
            ->withPivot('is_blood')
            ->withTimestamps();
    }

    public function connections()
    {
        return $this->belongsToMany(User::class, 'relations', 'user_id', 'related_user_id')
            ->wherePivot('type', 'connection')
            ->withPivot('is_blood')
            ->withTimestamps();
    }

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
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
