<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DataDetailController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SocialMediaController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FamilyEventController;
use App\Http\Controllers\FamilyTreeController;
use App\Http\Controllers\MasterDataController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

// Auth Routes (Socialite & OTP)
Route::get('auth/google', [SocialiteController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [SocialiteController::class, 'callback'])->name('auth.google.callback');

Route::post('login/otp/send', [OtpLoginController::class, 'sendCode'])->name('login.otp.send');
Route::post('login/otp/verify', [OtpLoginController::class, 'verifyCode'])->name('login.otp.verify');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Admin Panel
    Route::middleware('can:view_roles')->group(function () {
        Route::get('admin/roles', [RoleController::class, 'index'])->name('admin.roles.index');
        Route::post('admin/roles', [RoleController::class, 'store'])->name('admin.roles.store');
        Route::put('admin/roles/{role}', [RoleController::class, 'update'])->name('admin.roles.update');
        Route::delete('admin/roles/{role}', [RoleController::class, 'destroy'])->name('admin.roles.destroy');
        Route::put('admin/roles/{role}/restore', [RoleController::class, 'restore'])->name('admin.roles.restore');
        Route::delete('admin/roles/{role}/force', [RoleController::class, 'forceDestroy'])->name('admin.roles.force-destroy');
    });

    Route::middleware('can:view_users')->group(function () {
        Route::get('admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::put('admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::put('admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');
        Route::delete('admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        Route::put('admin/users/{user}/restore', [UserController::class, 'restore'])->name('admin.users.restore');
        Route::delete('admin/users/{user}/force', [UserController::class, 'forceDestroy'])->name('admin.users.force-destroy');
    });

    Route::middleware('can:view_master')->group(function () {
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

    Route::middleware('can:manage_settings')->group(function () {
        Route::get('admin/settings/wise-tree', [SettingController::class, 'index'])->name('admin.settings.wise-tree.index');
        Route::put('admin/settings/wise-tree', [SettingController::class, 'update'])->name('admin.settings.wise-tree.update');
        Route::get('admin/settings/backup', [SettingController::class, 'downloadBackup'])->name('admin.settings.backup');

        Route::get('admin/logs', [ActivityLogController::class, 'index'])->name('admin.logs.index');
    });

    Route::get('/tree/{user?}', [FamilyTreeController::class, 'show'])->name('tree.show');

    // Legacy Master Data
    Route::post('/api/master-data/social-media', [MasterDataController::class, 'storeSocialMedia'])->name('master-data.social-media.store');
    Route::delete('/api/master-data/social-media/{socialMedia}', [MasterDataController::class, 'destroySocialMedia'])->name('master-data.social-media.destroy');
    Route::post('/api/master-data/additional-fields', [MasterDataController::class, 'storeAdditionalField'])->name('master-data.additional-fields.store');
    Route::delete('/api/master-data/additional-fields/{additionalField}', [MasterDataController::class, 'destroyAdditionalField'])->name('master-data.additional-fields.destroy');

    Route::get('/api/users/search', [FamilyTreeController::class, 'search'])->name('users.search');
    Route::get('/api/locations', [FamilyTreeController::class, 'locations'])->name('locations.index');
    Route::get('/api/users/{user}/details', [FamilyTreeController::class, 'details'])->name('users.details');
    Route::get('/api/family-tree/mockup-kk', [FamilyTreeController::class, 'downloadMockupKk'])->name('family-tree.mockup-kk');
    Route::post('/api/users/{user}/extract-kk', [FamilyTreeController::class, 'extractKkExcel'])->name('users.extract-kk');
    Route::post('/api/users/{user}/update', [FamilyTreeController::class, 'updateProfile'])->name('users.update');
    Route::delete('/api/users/{user}', [FamilyTreeController::class, 'destroyMember'])->name('users.destroy');
    Route::post('/api/relations', [FamilyTreeController::class, 'storeRelation'])->name('relations.store');
    Route::post('/api/users/{user}/toggle-admin', [FamilyTreeController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::post('/api/users/{user}/toggle-head', [FamilyTreeController::class, 'toggleFamilyHead'])->name('users.toggle-head');
    Route::post('/api/users/{user}/upload-identity', [FamilyTreeController::class, 'uploadIdentity'])->name('users.upload-identity');
    Route::post('/api/users/{user}/letter', [FamilyTreeController::class, 'storeLetter'])->name('users.store-letter');
    Route::post('/api/letters/{letter}/read', [FamilyTreeController::class, 'markLetterAsRead'])->name('letters.mark-read');

    Route::get('family-events', [FamilyEventController::class, 'index'])->name('family-events.index');
    Route::post('family-events', [FamilyEventController::class, 'store'])->name('family-events.store');
    Route::get('family-events/{event}', [FamilyEventController::class, 'show'])->name('family-events.show');
    Route::put('family-events/{event}', [FamilyEventController::class, 'update'])->name('family-events.update');
    Route::delete('family-events/{event}', [FamilyEventController::class, 'destroy'])->name('family-events.destroy');
    Route::post('family-events/{event}/rsvp', [FamilyEventController::class, 'rsvp'])->name('family-events.rsvp');
});

require __DIR__.'/settings.php';
