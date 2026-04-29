<?php

namespace App\Services;

use App\Models\FamilyLetter;
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

    protected Collection $myLetters;

    public function __construct(
        protected RelationshipCalculator $calculator
    ) {}

    public function buildTree(User $rootUser, User $viewingUser, int $maxDepth = 3, array $expandedIds = []): array
    {
        $cacheKey = "family_tree_{$rootUser->id}_{$viewingUser->id}_depth_{$maxDepth}_exp_".implode('_', $expandedIds);

        return Cache::remember($cacheKey, 3600, function () use ($rootUser, $viewingUser, $maxDepth, $expandedIds) {
            $this->allUsers = User::with('roles')->get();
            $this->allProfiles = UserProfile::all();
            $this->allRelations = Relation::all();

            // Get all unread letters where the viewing user is the recipient
            $this->myLetters = FamilyLetter::where('recipient_id', $viewingUser->id)
                ->whereNull('read_at')
                ->get();

            return $this->buildNode($rootUser, $viewingUser, 1, $maxDepth, $expandedIds);
        });
    }

    private function buildNode(User $user, User $viewingUser, int $currentDepth, int $maxDepth, array $expandedIds): array
    {
        $profile = $this->allProfiles->where('user_id', $user->id)->first();
        $isAdmin = $user->can('manage_tree_all');

        $node = [
            'id' => (int) $user->id,
            'panggilan' => $this->calculator->calculate($viewingUser, $user, $this->allRelations),
            'full_name' => ($profile && $profile->full_name) ? $profile->full_name : $user->name,
            'photo_url' => ($profile && $profile->profile_photo_path) ? '/storage/'.$profile->profile_photo_path : null,
            'gender' => $profile->gender ?? 'M',
            'is_alive' => (bool) ($profile->is_alive ?? true),
            'birth_date' => ($profile && $profile->birth_date) ? $profile->birth_date->format('Y-m-d') : null,
            'death_date' => ($profile && $profile->death_date) ? $profile->death_date->format('Y-m-d') : null,
            'pekerjaan' => $profile->additional_info['pekerjaan'] ?? null,
            'is_admin' => $isAdmin,
            'depth' => $currentDepth,
            'spouse' => [],
            'children' => [],
            // Check if THIS node (sender) has a letter for ME (viewer)
            'has_letter_for_me' => $this->myLetters->contains(fn ($l) => $l->sender_id == $user->id && is_null($l->read_at)),
        ];

        $spouseRelations = $this->allRelations->where('user_id', $user->id)->where('type', 'spouse');
        $childRelations = $this->allRelations->where('user_id', $user->id)->where('type', 'child');

        $effectiveMaxDepth = in_array($user->id, $expandedIds) ? $currentDepth + 1 : $maxDepth;

        if ($currentDepth >= $effectiveMaxDepth) {
            $node['has_more'] = $spouseRelations->isNotEmpty() || $childRelations->isNotEmpty();

            return $node;
        }

        $myChildrenIds = $childRelations->pluck('related_user_id')->toArray();

        foreach ($spouseRelations as $rel) {
            $spouse = $this->allUsers->find($rel->related_user_id);
            if (! $spouse) {
                continue;
            }

            $sProfile = $this->allProfiles->where('user_id', $spouse->id)->first();
            $sIsAdmin = $spouse->can('manage_tree_all');
            $spouseChildrenRels = $this->allRelations->where('user_id', $spouse->id)->where('type', 'child');

            $spouseChildren = [];
            foreach ($spouseChildrenRels as $rel) {
                $child = $this->allUsers->find($rel->related_user_id);
                if ($child) {
                    $childNode = $this->buildNode($child, $viewingUser, $currentDepth + 1, $maxDepth, $expandedIds);
                    $isLinkedToBio = in_array($child->id, $myChildrenIds);
                    if ($isLinkedToBio) {
                        $bioRelToChild = $this->allRelations->where('user_id', $user->id)->where('related_user_id', $child->id)->where('type', 'child')->first();
                        $childNode['is_blood'] = (bool) ($bioRelToChild->is_blood ?? true);
                        $myChildrenIds = array_diff($myChildrenIds, [$child->id]);
                    } else {
                        $childNode['is_blood'] = false;
                    }
                    $spouseChildren[] = $childNode;
                }
            }

            $node['spouse'][] = [
                'id' => (int) $spouse->id,
                'panggilan' => $this->calculator->calculate($viewingUser, $spouse, $this->allRelations),
                'full_name' => $sProfile->full_name ?? $spouse->name,
                'photo_url' => $sProfile->profile_photo_path ? '/storage/'.$sProfile->profile_photo_path : null,
                'gender' => $sProfile->gender ?? 'M',
                'is_alive' => (bool) ($sProfile->is_alive ?? true),
                'birth_date' => ($sProfile && $sProfile->birth_date) ? $sProfile->birth_date->format('Y-m-d') : null,
                'death_date' => ($sProfile && $sProfile->death_date) ? $sProfile->death_date->format('Y-m-d') : null,
                'pekerjaan' => $sProfile->additional_info['pekerjaan'] ?? null,
                'is_admin' => $sIsAdmin,
                'children' => $spouseChildren,
                'has_letter_for_me' => $this->myLetters->contains(fn ($l) => $l->sender_id == $spouse->id && is_null($l->read_at)),
            ];
        }

        foreach ($myChildrenIds as $childId) {
            $child = $this->allUsers->find($childId);
            if ($child) {
                $node['children'][] = $this->buildNode($child, $viewingUser, $currentDepth + 1, $maxDepth, $expandedIds);
            }
        }

        return $node;
    }
}
