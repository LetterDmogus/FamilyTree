<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('/tree/{user?}', [\App\Http\Controllers\FamilyTreeController::class, 'show'])->name('tree.show');
    
    // Master Data Management
    Route::get('/settings/master-data', [\App\Http\Controllers\MasterDataController::class, 'index'])->name('master-data.index');
    Route::post('/api/master-data/social-media', [\App\Http\Controllers\MasterDataController::class, 'storeSocialMedia'])->name('master-data.social-media.store');
    Route::delete('/api/master-data/social-media/{socialMedia}', [\App\Http\Controllers\MasterDataController::class, 'destroySocialMedia'])->name('master-data.social-media.destroy');
    Route::post('/api/master-data/additional-fields', [\App\Http\Controllers\MasterDataController::class, 'storeAdditionalField'])->name('master-data.additional-fields.store');
    Route::delete('/api/master-data/additional-fields/{additionalField}', [\App\Http\Controllers\MasterDataController::class, 'destroyAdditionalField'])->name('master-data.additional-fields.destroy');

    Route::get('/api/users/search', [\App\Http\Controllers\FamilyTreeController::class, 'search'])->name('users.search');
    Route::get('/api/users/{user}/details', [\App\Http\Controllers\FamilyTreeController::class, 'details'])->name('users.details');
    Route::post('/api/users/{user}/update', [\App\Http\Controllers\FamilyTreeController::class, 'updateProfile'])->name('users.update');
    Route::delete('/api/users/{user}', [\App\Http\Controllers\FamilyTreeController::class, 'destroyMember'])->name('users.destroy');
    Route::post('/api/relations', [\App\Http\Controllers\FamilyTreeController::class, 'storeRelation'])->name('relations.store');
});

require __DIR__.'/settings.php';
