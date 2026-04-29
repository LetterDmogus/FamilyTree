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
        Schema::table('users', function (Blueprint $col) {
            $col->string('password')->nullable()->change();
            $col->string('google_id')->nullable()->unique()->after('password');
            $col->string('login_code')->nullable()->after('google_id');
            $col->timestamp('login_code_expires_at')->nullable()->after('login_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $col) {
            $col->string('password')->nullable(false)->change();
            $col->dropColumn(['google_id', 'login_code', 'login_code_expires_at']);
        });
    }
};
