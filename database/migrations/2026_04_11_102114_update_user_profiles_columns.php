<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->renameColumn('metadata', 'additional_info');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->json('social_media')->after('additional_info')->nullable();
            $table->string('profile_photo_path', 2048)->after('social_media')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['social_media', 'profile_photo_path']);
            $table->renameColumn('additional_info', 'metadata');
        });
    }
};
