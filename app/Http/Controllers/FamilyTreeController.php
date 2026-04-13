<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Relation;
use App\Models\UserProfile;
use App\Models\MasterAdditionalField;
use App\Models\MasterSocialMedia;
use App\Services\FamilyTreeService;
use App\Services\RelationshipCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class FamilyTreeController extends Controller
{
    public function __construct(
        protected FamilyTreeService $treeService,
        protected RelationshipCalculator $calculator
    ) {}

    public function show(User $user = null)
    {
        $viewer = auth()->user();
        $targetUser = $user ?? $viewer;

        $root = $this->findVisualRoot($targetUser);

        $tree = $this->treeService->buildTree($root, $viewer, 5);

        return Inertia::render('FamilyTree/Show', [
            'tree' => $tree,
            'rootUser' => $root,
            'master' => [
                'socialMedias' => MasterSocialMedia::all(),
                'additionalFields' => MasterAdditionalField::all(),
            ]
        ]);
    }

    private function findVisualRoot(User $user): User
    {
        // 1. If user has no biological parents, check if they have a spouse
        $parentId = Relation::where('related_user_id', $user->id)
            ->where('type', 'child')
            ->value('user_id');
        
        if (!$parentId) {
            // Check for spouse
            $spouseId = Relation::where('user_id', $user->id)
                ->where('type', 'spouse')
                ->value('related_user_id');
            
            if ($spouseId) {
                // If spouse found, try to find the root of the spouse instead
                return $this->findVisualRoot(User::find($spouseId));
            }

            return $user;
        }

        $parent = User::find($parentId);

        // 2. Try to find Grandfather/Grandmother (Look up another level)
        $grandParentId = Relation::where('related_user_id', $parent->id)
            ->where('type', 'child')
            ->value('user_id');

        if (!$grandParentId) {
            return $parent;
        }

        return User::find($grandParentId) ?? $parent;
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        
        $users = User::with('profile')
            ->where('name', 'like', "%{$query}%")
            ->orWhereHas('profile', function($q) use ($query) {
                $q->where('full_name', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->profile->full_name ?? $u->name,
                'email' => $u->email,
            ]);

        return response()->json($users);
    }

    public function details(User $user, Request $request)
    {
        $fromId = $request->input('from_id', auth()->id());
        $fromUser = User::find($fromId) ?? auth()->user();
        
        $user->load('profile');
        $relationLabel = $this->calculator->calculate($fromUser, $user);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'full_name' => $user->profile->full_name ?? $user->name,
            'email' => $user->email,
            'relation_label' => $relationLabel,
            'profile' => $user->profile,
        ]);
    }

    public function storeRelation(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:child,spouse',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'gender' => 'required|in:M,F',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:255',
            'profile_photo' => 'nullable|image|max:2048',
            'additional_info' => 'nullable|array',
            'social_media' => 'nullable|array',
        ]);

        $userId = $validated['user_id'];

        $user = User::create([
            'name' => explode(' ', $validated['full_name'])[0],
            'email' => $validated['email'],
            'password' => bcrypt(str()->random(16)),
        ]);

        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('profiles', 'public');
        }

        UserProfile::create([
            'user_id' => $user->id,
            'full_name' => $validated['full_name'],
            'gender' => $validated['gender'],
            'birth_date' => $validated['birth_date'],
            'birth_place' => $validated['birth_place'],
            'profile_photo_path' => $photoPath,
            'additional_info' => $validated['additional_info'] ?? [],
            'social_media' => $validated['social_media'] ?? [],
        ]);

        $isBlood = ($validated['type'] === 'child');
        
        Relation::create([
            'user_id' => $userId,
            'related_user_id' => $user->id,
            'type' => $validated['type'],
            'is_blood' => $isBlood,
        ]);

        if ($validated['type'] === 'spouse') {
            Relation::firstOrCreate([
                'user_id' => $user->id,
                'related_user_id' => $userId,
                'type' => 'spouse',
                'is_blood' => false,
            ]);
        }

        $this->clearTreeCache();

        return back()->with('success', 'Anggota keluarga berhasil ditambahkan.');
    }

    public function updateProfile(User $user, Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'gender' => 'required|in:M,F',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:255',
            'profile_photo' => 'nullable|image|max:2048',
            'additional_info' => 'nullable|array',
            'social_media' => 'nullable|array',
        ]);

        $user->update([
            'name' => explode(' ', $validated['full_name'])[0],
            'email' => $validated['email'],
        ]);

        $data = [
            'full_name' => $validated['full_name'],
            'gender' => $validated['gender'],
            'birth_date' => $validated['birth_date'],
            'birth_place' => $validated['birth_place'],
            'additional_info' => $validated['additional_info'] ?? [],
            'social_media' => $validated['social_media'] ?? [],
        ];

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profiles', 'public');
        }

        $user->profile->update($data);

        $this->clearTreeCache();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function destroyMember(User $user)
    {
        // Only allow if not family head
        if ($user->profile->is_family_head) {
            return back()->withErrors(['error' => 'Kepala keluarga tidak dapat dihapus.']);
        }

        // Delete user (cascade will handle profile and relations)
        $user->delete();

        $this->clearTreeCache();

        return back()->with('success', 'Anggota keluarga berhasil dihapus.');
    }

    private function clearTreeCache()
    {
        Cache::flush();
    }
}
