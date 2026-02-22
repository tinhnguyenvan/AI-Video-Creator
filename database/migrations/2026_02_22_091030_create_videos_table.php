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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('prompt');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('video_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('google_operation_name')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('duration')->nullable(); // seconds
            $table->string('resolution')->default('720p');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
