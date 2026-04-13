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
        Schema::create('relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('related_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['child', 'spouse', 'connection']);
            $table->boolean('is_blood')->default(true);
            $table->timestamps();
        });

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('full_name');
            $table->enum('gender', ['M', 'F']);
            $table->date('birth_date');
            $table->boolean('is_family_head')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('master_social_medias', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_social_medias');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('relations');
    }
};
