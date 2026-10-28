<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_account_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('subject_id')->constrained('users')->restrictOnDelete();
            $table->string('operation', 20);
            $table->string('reason', 500);
            $table->json('before_state');
            $table->json('after_state');
            $table->timestamps();
            $table->index(['created_at', 'id']);
            $table->index(['subject_id', 'created_at', 'id']);
            $table->index(['actor_id', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_account_events');
    }
};
