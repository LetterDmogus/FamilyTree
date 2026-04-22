<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected static function booted()
    {
        static::deleting(function (User $user) {
            // Tree Healing & Succession Logic
            \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
                // 1. Succession Planning (If this user is the family head)
                if ($user->profile?->is_family_head) {
                    $successor = null;

                    // Try to find spouse first
                    $spouse = $user->spouse()->first();
                    if ($spouse) {
                        $successor = $spouse;
                    } else {
                        // If no spouse, find eldest child
                        $successor = $user->children()
                            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                            ->orderBy('user_profiles.birth_date', 'asc')
                            ->first();
                    }

                    if ($successor) {
                        $successor->profile->update(['is_family_head' => true]);
                    }
                }

                // 2. Get parents (to re-attach children to them)
                $parentIds = Relation::where('related_user_id', $user->id)
                    ->where('type', 'child')
                    ->pluck('user_id');

                // 3. Get children
                $childrenIds = Relation::where('user_id', $user->id)
                    ->where('type', 'child')
                    ->pluck('related_user_id');

                // 4. Re-attach children to each parent (Grandparents)
                foreach ($parentIds as $parentId) {
                    foreach ($childrenIds as $childId) {
                        Relation::firstOrCreate([
                            'user_id' => $parentId,
                            'related_user_id' => $childId,
                            'type' => 'child',
                        ], [
                            'is_blood' => false,
                        ]);
                    }
                }

                // 5. Clean up all relations involving this user
                Relation::where('user_id', $user->id)
                    ->orWhere('related_user_id', $user->id)
                    ->delete();
                
                // 6. Clear tree cache
                \Illuminate\Support\Facades\Cache::flush();
            });
        });
    }

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

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhereHas('profile', function ($q) use ($search) {
                    $q->where('full_name', 'like', '%'.$search.'%');
                });
        });
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
