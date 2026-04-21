<?php

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Relation;
use App\Services\FamilyTreeService;
use App\Services\RelationshipCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('family tree service builds a 3-tier tree', function () {
    // Setup
    $kakek = User::factory()->create(['name' => 'Kakek']);
    $ayah = User::factory()->create(['name' => 'Ayah']);
    $anak = User::factory()->create(['name' => 'Anak']);

    Relation::create(['user_id' => $kakek->id, 'related_user_id' => $ayah->id, 'type' => 'child']);
    Relation::create(['user_id' => $ayah->id, 'related_user_id' => $anak->id, 'type' => 'child']);

    $service = new FamilyTreeService(new RelationshipCalculator());
    $tree = $service->buildTree($kakek, $kakek);

    expect($tree['full_name'])->toBe('Kakek');
    expect($tree['children'])->toHaveCount(1);
    expect($tree['children'][0]['full_name'])->toBe('Ayah');
    expect($tree['children'][0]['children'])->toHaveCount(1);
    expect($tree['children'][0]['children'][0]['full_name'])->toBe('Anak');
});

test('relationship calculator identifies child and parent', function () {
    $parent = User::factory()->create();
    $child = User::factory()->create();

    Relation::create(['user_id' => $parent->id, 'related_user_id' => $child->id, 'type' => 'child']);

    $calculator = new RelationshipCalculator();
    
    expect($calculator->calculate($parent, $child))->toBe('Anak Laki-laki');
    expect($calculator->calculate($child, $parent))->toBe('Ayah'); // Default M
});

test('relationship calculator identifies siblings', function () {
    $parent = User::factory()->create();
    $child1 = User::factory()->create();
    $child2 = User::factory()->create();

    Relation::create(['user_id' => $parent->id, 'related_user_id' => $child1->id, 'type' => 'child']);
    Relation::create(['user_id' => $parent->id, 'related_user_id' => $child2->id, 'type' => 'child']);

    $calculator = new RelationshipCalculator();
    
    expect($calculator->calculate($child1, $child2))->toBe('Saudara Kandung'); // No birth dates in factory
});

test('relationship calculator identifies self as saya', function () {
    $user = User::factory()->create();
    $calculator = new RelationshipCalculator();
    
    expect($calculator->calculate($user, $user))->toBe('saya');
});

test('relationship calculator identifies step-child and step-parent', function () {
    $parent = User::factory()->create();
    $child = User::factory()->create();

    // Step-child relationship
    Relation::create(['user_id' => $parent->id, 'related_user_id' => $child->id, 'type' => 'child', 'is_blood' => false]);

    $calculator = new RelationshipCalculator();
    
    expect($calculator->calculate($parent, $child))->toBe('Anak Tiri Laki-laki');
    expect($calculator->calculate($child, $parent))->toBe('Ayah Tiri');
});

test('family tree controller returns inertia show page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('tree.show'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('FamilyTree/Show')
        ->has('tree')
    );
});
