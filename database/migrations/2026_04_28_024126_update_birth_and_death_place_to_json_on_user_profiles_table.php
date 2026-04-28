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
            $table->dropColumn('birth_place');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->json('birth_place')->nullable()->after('birth_date');
            $table->json('death_place')->nullable()->after('birth_place');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['birth_place', 'death_place']);
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('birth_place')->nullable()->after('birth_date');
        });
    }
};
