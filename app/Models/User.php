<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\LogsActivity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'google_id', 'login_code', 'login_code_expires_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected static function booted()
    {
        static::deleting(function (User $user) {
            DB::transaction(function () use ($user) {
                // If Family Head is deleted, cascade delete children and spouses
                if ($user->profile?->is_family_head) {
                    // 1. Delete children (bloodline)
                    foreach ($user->children as $child) {
                        $child->delete();
                    }

                    // 2. Delete spouses
                    foreach ($user->spouse as $spouse) {
                        $spouse->delete();
                    }
                } else {
                    // Normal Tree Healing & Succession Logic for non-head members
                    // (Previous logic for regular members)

                    // 1. Succession Planning (If this user was somehow a head but not global head)
                    if ($user->profile?->is_family_head) {
                        $successor = $user->spouse()->first() ?? $user->children()
                            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                            ->orderBy('user_profiles.birth_date', 'asc')
                            ->first();

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
                }

                // Clean up all relations involving this user
                Relation::where('user_id', $user->id)
                    ->orWhere('related_user_id', $user->id)
                    ->delete();

                Cache::flush();
            });
        });
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function attachments()
    {
        return $this->hasMany(UserAttachment::class);
    }

    public function familyEvents()
    {
        return $this->hasMany(FamilyEvent::class);
    }

    public function rsvps()
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function sentLetters()
    {
        return $this->hasMany(FamilyLetter::class, 'sender_id');
    }

    public function receivedLetters()
    {
        return $this->hasMany(FamilyLetter::class, 'recipient_id');
    }

    public function children()
    {
        return $this->belongsToMany(User::class, 'relations', 'user_id', 'related_user_id')
            ->wherePivot('type', 'child')
            ->withPivot('is_blood')
            ->withTimestamps();
    }

    public function parents()
    {
        return $this->belongsToMany(User::class, 'relations', 'related_user_id', 'user_id')
            ->wherePivot('type', 'child')
            ->withPivot('is_blood')
            ->withTimestamps();
    }

    public function isParentOf(User $user): bool
    {
        return $this->children()->where('related_user_id', $user->id)->exists();
    }

    public function isChildOf(User $user): bool
    {
        return $this->parents()->where('user_id', $user->id)->exists();
    }

    public function isStepParentOf(User $user): bool
    {
        return $this->spouse()->whereHas('children', function ($q) use ($user) {
            $q->where('related_user_id', $user->id);
        })->exists();
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
            'login_code_expires_at' => 'datetime',
        ];
    }
}
