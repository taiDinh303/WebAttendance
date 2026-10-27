<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_series', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('class_id')->constrained('classes')->restrictOnDelete();
            $t->json('definition');
            $t->timestamps();
        });
        Schema::create('class_sessions', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignUlid('series_id')->nullable()->constrained('session_series')->restrictOnDelete();
            $t->string('title', 200)->nullable();
            $t->string('room', 120)->nullable();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->string('status', 20)->default('scheduled');
            $t->string('cancel_reason', 500)->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
            $t->index(['class_id', 'starts_at', 'id']);
        });
        Schema::create('attendance_windows', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('session_id')->unique()->constrained('class_sessions')->restrictOnDelete();
            $t->foreignUlid('opened_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('opens_at');
            $t->dateTime('late_after')->nullable();
            $t->dateTime('closes_at');
            $t->string('state', 20)->default('open');
            $t->unsignedInteger('token_version')->default(1);
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
            $t->index(['state', 'closes_at']);
        });
        Schema::create('attendance_rosters', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('window_id')->constrained('attendance_windows')->restrictOnDelete();
            $t->foreignUlid('student_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['window_id', 'student_id']);
        });
        Schema::create('attendance_records', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('session_id')->constrained('class_sessions')->restrictOnDelete();
            $t->foreignUlid('student_id')->constrained('users')->restrictOnDelete();
            $t->foreignUlid('window_id')->constrained('attendance_windows')->restrictOnDelete();
            $t->string('status', 20);
            $t->dateTime('checked_in_at')->nullable();
            $t->string('recorded_source', 30);
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
            $t->unique(['session_id', 'student_id']);
            $t->index(['student_id', 'session_id']);
        });
        Schema::create('attendance_audits', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('record_id')->constrained('attendance_records')->restrictOnDelete();
            $t->foreignUlid('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('action', 30);
            $t->string('reason', 500)->nullable();
            $t->json('before_data')->nullable();
            $t->json('after_data');
            $t->timestamps();
            $t->index(['record_id', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        foreach (['attendance_audits', 'attendance_records', 'attendance_rosters', 'attendance_windows', 'class_sessions', 'session_series'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
