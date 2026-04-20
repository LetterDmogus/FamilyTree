<?php

namespace App\Services;

use App\Models\User;
use App\Models\Relation;
use App\Models\UserProfile;
use Illuminate\Support\Collection;

class FamilyTreeService
{
    protected Collection $allUsers;
    protected Collection $allProfiles;
    protected Collection $allRelations;

    public function __construct(
        protected RelationshipCalculator $calculator
    ) {}

    public function buildTree(User $rootUser, User $viewingUser, int $maxDepth = 3): array
    {
        $cacheKey = "family_tree_{$rootUser->id}_{$viewingUser->id}_depth_{$maxDepth}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($rootUser, $viewingUser, $maxDepth) {
            $this->allUsers = User::with('roles')->get();
            $this->allProfiles = UserProfile::all();
            $this->allRelations = Relation::all();

            return $this->buildNode($rootUser, $viewingUser, 1, $maxDepth);
        });
    }

    private function buildNode(User $user, User $viewingUser, int $currentDepth, int $maxDepth): array
    {
        $profile = $this->allProfiles->where('user_id', $user->id)->first();
        $isAdmin = $user->roles->whereIn('name', ['admin', 'superadmin'])->isNotEmpty();
        
        $node = [
            'id'          => (int) $user->id,
            'panggilan'   => $this->calculator->calculate($viewingUser, $user, $this->allRelations),
            'full_name'   => $profile->full_name ?? $user->name,
            'photo_url'   => $profile->photo_url ?? null,
            'gender'      => $profile->gender ?? 'M',
            'is_alive'    => $profile->is_alive ?? true,
            'birth_date'  => $profile->birth_date ? $profile->birth_date->format('Y-m-d') : null,
            'death_date'  => $profile->death_date ? $profile->death_date->format('Y-m-d') : null,
            'pekerjaan'   => $profile->additional_info['pekerjaan'] ?? null,
            'is_admin'    => $isAdmin,
            'depth'       => $currentDepth,
            'spouse'      => [],
            'children'    => [],
        ];

        if ($currentDepth >= $maxDepth) {
            return $node;
        }

        $spouseRelations = $this->allRelations->where('user_id', $user->id)->where('type', 'spouse');
        foreach ($spouseRelations as $rel) {
            $spouse = $this->allUsers->find($rel->related_user_id);
            if (!$spouse) continue;

            $sProfile = $this->allProfiles->where('user_id', $spouse->id)->first();
            $sIsAdmin = $spouse->roles->whereIn('name', ['admin', 'superadmin'])->isNotEmpty();

            $node['spouse'][] = [
                'id'          => (int) $spouse->id,
                'panggilan'   => $this->calculator->calculate($viewingUser, $spouse, $this->allRelations),
                'full_name'   => $sProfile->full_name ?? $spouse->name,
                'photo_url'   => $sProfile->photo_url ?? null,
                'gender'    => $sProfile->gender ?? 'M',
                'is_alive'  => $sProfile->is_alive ?? true,
                'birth_date'=> $sProfile->birth_date ? $sProfile->birth_date->format('Y-m-d') : null,
                'death_date'=> $sProfile->death_date ? $sProfile->death_date->format('Y-m-d') : null,
                'pekerjaan' => $sProfile->additional_info['pekerjaan'] ?? null,
                'is_admin'  => $sIsAdmin,
            ];
        }

        $childRelations = $this->allRelations->where('user_id', $user->id)->where('type', 'child');
        foreach ($childRelations as $rel) {
            $child = $this->allUsers->find($rel->related_user_id);
            if ($child) {
                $node['children'][] = $this->buildNode($child, $viewingUser, $currentDepth + 1, $maxDepth);
            }
        }

        return $node;
    }
}
