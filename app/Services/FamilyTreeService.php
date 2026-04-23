<?php

namespace App\Services;

use App\Models\Relation;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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

        return Cache::remember($cacheKey, 3600, function () use ($rootUser, $viewingUser, $maxDepth) {
            $this->allUsers = User::with('roles')->get();
            $this->allProfiles = UserProfile::all();
            $this->allRelations = Relation::all();

            return $this->buildNode($rootUser, $viewingUser, 1, $maxDepth);
        });
    }

    private function buildNode(User $user, User $viewingUser, int $currentDepth, int $maxDepth): array
    {
        $profile = $this->allProfiles->where('user_id', $user->id)->first();
        $isAdmin = $user->can('manage_tree_all');

        $node = [
            'id' => (int) $user->id,
            'panggilan' => $this->calculator->calculate($viewingUser, $user, $this->allRelations),
            'full_name' => $profile->full_name ?? $user->name,
            'photo_url' => $profile->photo_url ?? null,
            'gender' => $profile->gender ?? 'M',
            'is_alive' => (bool) ($profile->is_alive ?? true),
            'birth_date' => ($profile && $profile->birth_date) ? $profile->birth_date->format('Y-m-d') : null,
            'death_date' => ($profile && $profile->death_date) ? $profile->death_date->format('Y-m-d') : null,
            'pekerjaan' => $profile->additional_info['pekerjaan'] ?? null,
            'is_admin' => $isAdmin,
            'depth' => $currentDepth,
            'spouse' => [],
            'children' => [],
        ];

        if ($currentDepth >= $maxDepth) {
            return $node;
        }

        $spouseRelations = $this->allRelations->where('user_id', $user->id)->where('type', 'spouse');
        $childRelations = $this->allRelations->where('user_id', $user->id)->where('type', 'child');

        // Find all children IDs for this user
        $myChildrenIds = $childRelations->pluck('related_user_id')->toArray();

        foreach ($spouseRelations as $rel) {
            $spouse = $this->allUsers->find($rel->related_user_id);
            if (! $spouse) {
                continue;
            }

            $sProfile = $this->allProfiles->where('user_id', $spouse->id)->first();
            $sIsAdmin = $spouse->can('manage_tree_all');

            // Find children that belong to BOTH this user and this spouse
            $commonChildrenRels = $this->allRelations
                ->where('user_id', $spouse->id)
                ->where('type', 'child')
                ->whereIn('related_user_id', $myChildrenIds);

            $spouseChildren = [];
            foreach ($commonChildrenRels as $rel) {
                $child = $this->allUsers->find($rel->related_user_id);
                if ($child) {
                    $childNode = $this->buildNode($child, $viewingUser, $currentDepth + 1, $maxDepth);
                    // Add is_blood relative to this node's parent (the current user/spouse pair)
                    // We check the relation from the *current node user* to the child
                    $myRelToChild = $this->allRelations
                        ->where('user_id', $user->id)
                        ->where('related_user_id', $child->id)
                        ->where('type', 'child')
                        ->first();

                    $childNode['is_blood'] = (bool) ($myRelToChild->is_blood ?? true);

                    $spouseChildren[] = $childNode;
                    // Mark as "handled" so they don't appear in the main children list
                    $myChildrenIds = array_diff($myChildrenIds, [$child->id]);
                }
            }

            $node['spouse'][] = [
                'id' => (int) $spouse->id,
                'panggilan' => $this->calculator->calculate($viewingUser, $spouse, $this->allRelations),
                'full_name' => $sProfile->full_name ?? $spouse->name,
                'photo_url' => $sProfile->photo_url ?? null,
                'gender' => $sProfile->gender ?? 'M',
                'is_alive' => (bool) ($sProfile->is_alive ?? true),
                'birth_date' => ($sProfile && $sProfile->birth_date) ? $sProfile->birth_date->format('Y-m-d') : null,
                'death_date' => ($sProfile && $sProfile->death_date) ? $sProfile->death_date->format('Y-m-d') : null,
                'pekerjaan' => $sProfile->additional_info['pekerjaan'] ?? null,
                'is_admin' => $sIsAdmin,
                'children' => $spouseChildren, // Children specific to this spouse
            ];
        }

        // Remaining children (those not associated with any specific spouse)
        foreach ($myChildrenIds as $childId) {
            $child = $this->allUsers->find($childId);
            if ($child) {
                $node['children'][] = $this->buildNode($child, $viewingUser, $currentDepth + 1, $maxDepth);
            }
        }

        return $node;
    }
}
