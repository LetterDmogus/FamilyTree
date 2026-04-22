<?php

namespace App\Services;

use App\Models\User;
use App\Models\Relation;
use Illuminate\Support\Collection;

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

        $ups   = count(array_filter($path, fn($p) => $p['type'] === 'up'));
        $downs = count(array_filter($path, fn($p) => $p['type'] === 'down'));
        
        // Step family detection: only if a PARENT/CHILD link is non-biological.
        // We exclude 'spouse' from this check because spouses are never 'blood' in this system context,
        // and including them causes in-laws (e.g. child's spouse) to be labeled as 'step'.
        $hasStep = false;
        foreach ($path as $step) {
            if ($step['type'] !== 'spouse' && $step['is_blood'] === false) {
                $hasStep = true;
                break;
            }
        }
        
        $startsViaSpouse = (count($path) > 0 && $path[0]['type'] === 'spouse');
        $hasSpouseStep = in_array('spouse', array_column($path, 'type'));
        
        // 1. Determine POV Group
        $pov = 'direct';
        if ($startsViaSpouse) {
            $pov = 'via_spouse';
        } elseif ($hasSpouseStep) {
            $pov = 'in_law';
        }

        // 2. Biological exceptions within POV
        $isSpouseDescendant = ($startsViaSpouse && $ups === 0);
        $isSpouseAncestor = (count($path) > 0 && end($path)['type'] === 'spouse' && $downs === 0 && $hasSpouseStep && !$startsViaSpouse);
        
        if ($isSpouseDescendant || $isSpouseAncestor) {
            $pov = 'direct';
        }

        // 3. Direct Spouse
        if (count($path) === 1 && $path[0]['type'] === 'spouse') {
            return $this->getLabel("direct.spouse_{$gender}");
        }

        // 4. Siblings
        if ($ups === 1 && $downs === 1) {
            return $this->getSiblingLabel($from, $to, $gender, $pov, $hasStep);
        }

        // 5. Pattern Matching
        $pattern = $downs === 0 ? "up_{$ups}" : ($ups === 0 ? "down_{$downs}" : "up_{$ups}_down_{$downs}");
        $stepSuffix = $hasStep ? '_step' : '';
        
        return $this->getLabel("{$pov}.{$pattern}_{$gender}{$stepSuffix}") 
            ?? $this->getLabel("{$pov}.{$pattern}{$stepSuffix}")
            ?? $this->getLabel("direct.{$pattern}_{$gender}{$stepSuffix}")
            ?? $this->getLabel("direct.{$pattern}{$stepSuffix}")
            ?? $this->getLabel("{$pov}.{$pattern}_{$gender}") 
            ?? $this->getLabel("direct.{$pattern}_{$gender}")
            ?? $this->getLabel('default');
    }

    private function getSiblingLabel(User $from, User $to, string $gender, string $pov, bool $hasStep): string
    {
        $from->loadMissing('profile');
        $to->loadMissing('profile');

        $stepSuffix = $hasStep ? '_step' : '';

        if ($pov !== 'direct') {
            return $this->getLabel("{$pov}.sibling_{$gender}{$stepSuffix}") 
                ?? $this->getLabel("{$pov}.sibling_default{$stepSuffix}")
                ?? $this->getLabel("{$pov}.sibling_{$gender}")
                ?? $this->getLabel("{$pov}.sibling_default");
        }

        $fromBirth = $from->profile->birth_date ?? null;
        $toBirth = $to->profile->birth_date ?? null;

        if (!$fromBirth || !$toBirth) {
            return $this->getLabel("direct.sibling_default{$stepSuffix}") ?? $this->getLabel('direct.sibling_default');
        }

        $age = $toBirth->isBefore($fromBirth) ? 'older' : 'younger';
        return $this->getLabel("direct.sibling_{$gender}_{$age}{$stepSuffix}") 
            ?? $this->getLabel("direct.sibling_default{$stepSuffix}")
            ?? $this->getLabel("direct.sibling_{$gender}_{$age}")
            ?? $this->getLabel('direct.sibling_default');
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

            // Spouses (Always is_blood = false)
            foreach ($currentRelations->where('type', 'spouse') as $rel) {
                if ($rel->related_user_id == $to->id) return [...$path, ['type' => 'spouse', 'is_blood' => false]];
                if (!in_array($rel->related_user_id, $visited)) {
                    $visited[] = $rel->related_user_id;
                    $queue[]   = [$rel->related_user_id, [...$path, ['type' => 'spouse', 'is_blood' => false]]];
                }
            }

            // Parents (Going UP)
            foreach ($incomingRelations->where('type', 'child') as $rel) {
                if ($rel->user_id == $to->id) return [...$path, ['type' => 'up', 'is_blood' => (bool)$rel->is_blood]];
                if (!in_array($rel->user_id, $visited)) {
                    $visited[] = $rel->user_id;
                    $queue[]   = [$rel->user_id, [...$path, ['type' => 'up', 'is_blood' => (bool)$rel->is_blood]]];
                }
            }

            // Children (Going DOWN)
            foreach ($currentRelations->where('type', 'child') as $rel) {
                if ($rel->related_user_id == $to->id) return [...$path, ['type' => 'down', 'is_blood' => (bool)$rel->is_blood]];
                if (!in_array($rel->related_user_id, $visited)) {
                    $visited[] = $rel->related_user_id;
                    $queue[]   = [$rel->related_user_id, [...$path, ['type' => 'down', 'is_blood' => (bool)$rel->is_blood]]];
                }
            }
        }

        return null;
    }
}
