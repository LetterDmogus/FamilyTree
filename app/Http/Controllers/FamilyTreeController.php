<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Relation;
use App\Models\UserProfile;
use App\Models\MasterAdditionalField;
use App\Models\MasterSocialMedia;
use App\Services\FamilyTreeService;
use App\Services\RelationshipCalculator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class FamilyTreeController extends Controller
{
    use AuthorizesRequests;

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
            ],
            'can' => [
                'manage_all' => $viewer->can('manage_tree_all'),
                'manage_self' => $viewer->can('manage_tree_self'),
                'delete_all' => $viewer->can('delete_node_all'),
            ]
        ]);
    }

    private function findVisualRoot(User $user): User
    {
        $parentId = Relation::where('related_user_id', $user->id)
            ->where('type', 'child')
            ->value('user_id');
        
        if (!$parentId) {
            $spouseId = Relation::where('user_id', $user->id)
                ->where('type', 'spouse')
                ->value('related_user_id');
            
            if ($spouseId) {
                return $this->findVisualRoot(User::find($spouseId));
            }

            return $user;
        }

        $parent = User::find($parentId);

        $grandParentId = Relation::where('related_user_id', $parent->id)
            ->where('type', 'child')
            ->value('user_id');

        if (!$grandParentId) {
            return $parent;
        }

        return User::find($grandParentId) ?? $parent;
    }

    public function details(User $user, Request $request)
    {
        $fromId = $request->input('from_id', auth()->id());
        $fromUser = User::find($fromId) ?? auth()->user();
        
        $user->load(['profile', 'roles']);
        $relationLabel = $this->calculator->calculate($fromUser, $user);

        // Enhance social media data with prefixes
        $profile = $user->profile;
        if ($profile && !empty($profile->social_media)) {
            $platforms = MasterSocialMedia::all();
            $enhancedSocial = array_map(function($sm) use ($platforms) {
                $platform = $platforms->firstWhere('name', $sm['platform_name']);
                return array_merge($sm, [
                    'prefix' => $platform ? $platform->prefix : ''
                ]);
            }, $profile->social_media);
            $profile->social_media = $enhancedSocial;
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'full_name' => $user->profile->full_name ?? $user->name,
            'email' => $user->email,
            'relation_label' => $relationLabel,
            'profile' => $profile,
            'can' => [
                'toggle_admin' => auth()->user()->can('manage_roles'),
                'delete' => auth()->user()->can('delete_node_all') && !$user->profile?->is_family_head,
                'edit' => auth()->user()->can('manage_tree_all') || (auth()->user()->can('manage_tree_self') && auth()->id() === $user->id),
            ]
        ]);
    }

    public function storeRelation(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:child,spouse,parent',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'gender' => 'required|in:M,F',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:255',
            'profile_photo' => 'nullable|image|max:2048',
            'is_alive' => 'boolean',
            'death_date' => 'nullable|date',
            'additional_info' => 'nullable|array',
            'social_media' => 'nullable|array',
        ]);

        $targetUserId = $validated['user_id'];
        
        // Authorization check
        $canManage = auth()->user()->can('manage_tree_all') || 
                    (auth()->user()->can('manage_tree_self') && auth()->id() == $targetUserId);
        
        if (!$canManage) {
            abort(403, 'Anda tidak memiliki izin untuk menambah anggota ini.');
        }

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
            'is_alive' => $validated['is_alive'] ?? true,
            'death_date' => $validated['death_date'] ?? null,
            'profile_photo_path' => $photoPath,
            'additional_info' => $validated['additional_info'] ?? [],
            'social_media' => $validated['social_media'] ?? [],
        ]);

        $user->assignRole('member');

        if ($validated['type'] === 'child') {
            Relation::create([
                'user_id' => $targetUserId,
                'related_user_id' => $user->id,
                'type' => 'child',
                'is_blood' => true,
            ]);
        } elseif ($validated['type'] === 'spouse') {
            Relation::create(['user_id' => $targetUserId, 'related_user_id' => $user->id, 'type' => 'spouse', 'is_blood' => false]);
            Relation::create(['user_id' => $user->id, 'related_user_id' => $targetUserId, 'type' => 'spouse', 'is_blood' => false]);
        } elseif ($validated['type'] === 'parent') {
            Relation::create([
                'user_id' => $user->id,
                'related_user_id' => $targetUserId,
                'type' => 'child',
                'is_blood' => true,
            ]);
        }

        $this->clearTreeCache();

        return back()->with('success', 'Anggota keluarga berhasil ditambahkan.');
    }

    public function updateProfile(User $user, Request $request)
    {
        // Authorization check
        $canEdit = auth()->user()->can('manage_tree_all') || 
                  (auth()->user()->can('manage_tree_self') && auth()->id() === $user->id);
        
        if (!$canEdit) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit profil ini.');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'gender' => 'required|in:M,F',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:255',
            'is_alive' => 'boolean',
            'death_date' => 'nullable|date',
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
            'is_alive' => $validated['is_alive'] ?? true,
            'death_date' => $validated['death_date'] ?? null,
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

    public function toggleAdmin(User $user)
    {
        $this->authorize('manage_roles');

        if ($user->hasRole('admin')) {
            $user->removeRole('admin');
            $message = 'Hak akses admin dicabut.';
        } else {
            $user->assignRole('admin');
            $message = 'Anggota berhasil dipromosikan menjadi admin.';
        }

        $this->clearTreeCache();

        return back()->with('success', $message);
    }

    public function destroyMember(User $user)
    {
        $this->authorize('delete_node_all');

        if ($user->profile->is_family_head) {
            return back()->withErrors(['error' => 'Kepala keluarga tidak dapat dihapus.']);
        }

        $user->delete();
        $this->clearTreeCache();

        return back()->with('success', 'Anggota keluarga berhasil dihapus.');
    }

    private function clearTreeCache()
    {
        Cache::flush();
    }
}
