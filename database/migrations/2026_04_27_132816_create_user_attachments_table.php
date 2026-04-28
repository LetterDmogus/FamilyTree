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
        Schema::create('user_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('category'); // identity, history, note
            $table->string('type');     // kk, ktp, profile_photo, text
            $table->string('file_path')->nullable();
            $table->text('content')->nullable(); // For notes or extra metadata
            $table->boolean('is_private')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_attachments');
    }
};
