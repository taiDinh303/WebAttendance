<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('name', 120);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['owner_id', 'name', 'id']);
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('name', 120);
            $table->string('code', 50)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['owner_id', 'code']);
            $table->index(['owner_id', 'name', 'id']);
        });
        Schema::create('classes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('semester_id')->nullable()->constrained('semesters')->restrictOnDelete();
            $table->foreignUlid('course_id')->nullable()->constrained('courses')->restrictOnDelete();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('timezone', 64)->default('Asia/Ho_Chi_Minh');
            $table->string('status', 20)->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['owner_id', 'status', 'updated_at', 'id']);
        });
        Schema::create('class_invites', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('class_id')->unique()->constrained('classes')->restrictOnDelete();
            $table->char('code_hash', 64)->unique();
            $table->boolean('enabled')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('class_memberships', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignUlid('student_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20);
            $table->timestamp('requested_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('review_note', 500)->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['class_id', 'student_id']);
            $table->index(['student_id', 'status', 'class_id']);
            $table->index(['class_id', 'status', 'requested_at', 'id']);
        });
        Schema::create('membership_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('membership_id')->constrained('class_memberships')->restrictOnDelete();
            $table->foreignUlid('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('reason', 500)->nullable();
            $table->timestamps();
        });
        Schema::create('inbox_notifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('recipient_id')->constrained('users')->restrictOnDelete();
            $table->ulid('event_key');
            $table->string('type', 80);
            $table->ulid('resource_id');
            $table->json('payload');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['recipient_id', 'event_key']);
            $table->index(['recipient_id', 'read_at', 'created_at', 'id']);
        });
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('event_key')->unique();
            $table->string('type', 80);
            $table->ulid('aggregate_id');
            $table->json('payload');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();
            $table->index(['dispatched_at', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        foreach (['outbox_events', 'inbox_notifications', 'membership_events', 'class_memberships', 'class_invites', 'classes', 'courses', 'semesters'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
