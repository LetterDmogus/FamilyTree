<?php

namespace App\Services;

use App\Models\User;
use App\Models\Relation;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class RelationshipCalculator
{
    public function calculate(User $from, User $to, ?Collection $allRelations = null): string
    {
        if ($from->id === $to->id) {
            return $this->getLabel('direct.self') ?? 'saya';
        }

        $path = $this->findPath($from, $to, $allRelations);

        if (!$path) {
            return $this->getLabel('default') ?? 'Teman';
        }

        return $this->interpretPath($path, $from, $to) ?? 'Unknown';
    }

    private function interpretPath(array $path, User $from, User $to): string
    {
        $to->loadMissing('profile');
        $gender = $to->profile->gender ?? 'M';

        $ups   = count(array_filter($path, fn($p) => $p === 'up'));
        $downs = count(array_filter($path, fn($p) => $p === 'down'));
        
        $startsViaSpouse = (count($path) > 0 && $path[0] === 'spouse');
        $hasSpouseStep = in_array('spouse', $path);
        
        // 1. Determine POV Group
        $pov = 'direct';
        if ($startsViaSpouse) {
            $pov = 'via_spouse';
        } elseif ($hasSpouseStep) {
            $pov = 'in_law';
        }

        // 2. Biological exceptions within POV
        $isSpouseDescendant = ($startsViaSpouse && $ups === 0);
        $isSpouseAncestor = (count($path) > 0 && end($path) === 'spouse' && $downs === 0 && $hasSpouseStep && !$startsViaSpouse);
        
        if ($isSpouseDescendant || $isSpouseAncestor) {
            $pov = 'direct';
        }

        // 3. Direct Spouse
        if (count($path) === 1 && $path[0] === 'spouse') {
            return $this->getLabel("direct.spouse_{$gender}");
        }

        // 4. Siblings
        if ($ups === 1 && $downs === 1) {
            return $this->getSiblingLabel($from, $to, $gender, $pov);
        }

        // 5. Pattern Matching
        $pattern = $downs === 0 ? "up_{$ups}" : ($ups === 0 ? "down_{$downs}" : "up_{$ups}_down_{$downs}");
        
        return $this->getLabel("{$pov}.{$pattern}_{$gender}") 
            ?? $this->getLabel("{$pov}.{$pattern}")
            ?? $this->getLabel("direct.{$pattern}_{$gender}")
            ?? $this->getLabel("direct.{$pattern}")
            ?? $this->getLabel('default');
    }

    private function getSiblingLabel(User $from, User $to, string $gender, string $pov): string
    {
        $from->loadMissing('profile');
        $to->loadMissing('profile');

        if ($pov !== 'direct') {
            return $this->getLabel("{$pov}.sibling_{$gender}") ?? $this->getLabel("{$pov}.sibling_default");
        }

        $fromBirth = $from->profile->birth_date ?? null;
        $toBirth = $to->profile->birth_date ?? null;

        if (!$fromBirth || !$toBirth) {
            return $this->getLabel('direct.sibling_default');
        }

        $age = $toBirth->isBefore($fromBirth) ? 'older' : 'younger';
        return $this->getLabel("direct.sibling_{$gender}_{$age}") ?? $this->getLabel('direct.sibling_default');
    }

    private function getLabel(string $key): ?string
    {
        return config("family-tree.labels.{$key}");
    }

    private function findPath(User $from, User $to, ?Collection $allRelations = null): ?array
    {
        $queue   = [[$from->id, []]];
        $visited = [$from->id];

        while (!empty($queue)) {
            [$currentId, $path] = array_shift($queue);

            if (count($path) > 6) continue;

            $currentRelations = $allRelations 
                ? $allRelations->where('user_id', $currentId) 
                : Relation::where('user_id', $currentId)->get();

            $incomingRelations = $allRelations
                ? $allRelations->where('related_user_id', $currentId)
                : Relation::where('related_user_id', $currentId)->get();

            $spouses = $currentRelations->where('type', 'spouse');
            foreach ($spouses as $rel) {
                if ($rel->related_user_id == $to->id) return [...$path, 'spouse'];
                if (!in_array($rel->related_user_id, $visited)) {
                    $visited[] = $rel->related_user_id;
                    $queue[]   = [$rel->related_user_id, [...$path, 'spouse']];
                }
            }

            $parents = $incomingRelations->where('type', 'child');
            foreach ($parents as $rel) {
                if ($rel->user_id == $to->id) return [...$path, 'up'];
                if (!in_array($rel->user_id, $visited)) {
                    $visited[] = $rel->user_id;
                    $queue[]   = [$rel->user_id, [...$path, 'up']];
                }
            }

            $children = $currentRelations->where('type', 'child');
            foreach ($children as $rel) {
                if ($rel->related_user_id == $to->id) return [...$path, 'down'];
                if (!in_array($rel->related_user_id, $visited)) {
                    $visited[] = $rel->related_user_id;
                    $queue[]   = [$rel->related_user_id, [...$path, 'down']];
                }
            }
        }

        return null;
    }
}
