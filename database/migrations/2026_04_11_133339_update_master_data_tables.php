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
        Schema::table('master_social_medias', function (Blueprint $table) {
            $table->string('prefix')->nullable()->after('name');
        });

        Schema::create('master_additional_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon_key')->nullable();
            $table->enum('input_type', ['text', 'textarea', 'date', 'select']);
            $table->json('options')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_additional_fields');
        Schema::table('master_social_medias', function (Blueprint $table) {
            $table->dropColumn('prefix');
        });
    }
};
