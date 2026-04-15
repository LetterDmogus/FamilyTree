<?php

use App\Http\Controllers\Admin\DataDetailController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SocialMediaController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\FamilyTreeController;
use App\Http\Controllers\MasterDataController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Admin Panel
    Route::middleware('can:manage_roles')->group(function () {
        Route::get('admin/roles', [RoleController::class, 'index'])->name('admin.roles.index');
        Route::post('admin/roles', [RoleController::class, 'store'])->name('admin.roles.store');
        Route::put('admin/roles/{role}', [RoleController::class, 'update'])->name('admin.roles.update');
        Route::delete('admin/roles/{role}', [RoleController::class, 'destroy'])->name('admin.roles.destroy');
        Route::put('admin/roles/{role}/restore', [RoleController::class, 'restore'])->name('admin.roles.restore');
        Route::delete('admin/roles/{role}/force', [RoleController::class, 'forceDestroy'])->name('admin.roles.force-destroy');
    });

    Route::middleware('can:manage_users')->group(function () {
        Route::get('admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::delete('admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        Route::put('admin/users/{user}/restore', [UserController::class, 'restore'])->name('admin.users.restore');
        Route::delete('admin/users/{user}/force', [UserController::class, 'forceDestroy'])->name('admin.users.force-destroy');
    });

    Route::middleware('can:manage_master_data')->group(function () {
        // Social Media
        Route::get('admin/social-medias', [SocialMediaController::class, 'index'])->name('admin.social-medias.index');
        Route::post('admin/social-medias', [SocialMediaController::class, 'store'])->name('admin.social-medias.store');
        Route::put('admin/social-medias/{socialMedia}', [SocialMediaController::class, 'update'])->name('admin.social-medias.update');
        Route::delete('admin/social-medias/{socialMedia}', [SocialMediaController::class, 'destroy'])->name('admin.social-medias.destroy');
        Route::put('admin/social-medias/{socialMedia}/restore', [SocialMediaController::class, 'restore'])->name('admin.social-medias.restore');
        Route::delete('admin/social-medias/{socialMedia}/force', [SocialMediaController::class, 'forceDestroy'])->name('admin.social-medias.force-destroy');

        // Data Details
        Route::get('admin/data-details', [DataDetailController::class, 'index'])->name('admin.data-details.index');
        Route::post('admin/data-details', [DataDetailController::class, 'store'])->name('admin.data-details.store');
        Route::put('admin/data-details/{dataDetail}', [DataDetailController::class, 'update'])->name('admin.data-details.update');
        Route::delete('admin/data-details/{dataDetail}', [DataDetailController::class, 'destroy'])->name('admin.data-details.destroy');
        Route::put('admin/data-details/{dataDetail}/restore', [DataDetailController::class, 'restore'])->name('admin.data-details.restore');
        Route::delete('admin/data-details/{dataDetail}/force', [DataDetailController::class, 'forceDestroy'])->name('admin.data-details.force-destroy');
    });

    Route::get('/tree/{user?}', [FamilyTreeController::class, 'show'])->name('tree.show');

    // Legacy Master Data (can be removed later if not used by frontend directly anymore)
    Route::post('/api/master-data/social-media', [MasterDataController::class, 'storeSocialMedia'])->name('master-data.social-media.store');
    Route::delete('/api/master-data/social-media/{socialMedia}', [MasterDataController::class, 'destroySocialMedia'])->name('master-data.social-media.destroy');
    Route::post('/api/master-data/additional-fields', [MasterDataController::class, 'storeAdditionalField'])->name('master-data.additional-fields.store');
    Route::delete('/api/master-data/additional-fields/{additionalField}', [MasterDataController::class, 'destroyAdditionalField'])->name('master-data.additional-fields.destroy');

    Route::get('/api/users/search', [FamilyTreeController::class, 'search'])->name('users.search');
    Route::get('/api/users/{user}/details', [FamilyTreeController::class, 'details'])->name('users.details');
    Route::post('/api/users/{user}/update', [FamilyTreeController::class, 'updateProfile'])->name('users.update');
    Route::delete('/api/users/{user}', [FamilyTreeController::class, 'destroyMember'])->name('users.destroy');
    Route::post('/api/relations', [FamilyTreeController::class, 'storeRelation'])->name('relations.store');
});

require __DIR__.'/settings.php';
