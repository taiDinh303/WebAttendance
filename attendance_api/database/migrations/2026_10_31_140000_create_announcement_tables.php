<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignUlid('author_id')->constrained('users')->restrictOnDelete();
            $table->string('state', 20)->default('draft');
            $table->string('title', 200);
            $table->text('body')->nullable();
            $table->boolean('pinned')->default(false);
            $table->boolean('hidden')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['class_id', 'state', 'pinned', 'hidden', 'created_at', 'id']);
        });
        Schema::create('announcement_comments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('announcement_id')->constrained('announcements')->restrictOnDelete();
            $table->foreignUlid('author_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->boolean('hidden')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['announcement_id', 'hidden', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_comments');
        Schema::dropIfExists('announcements');
    }
};
