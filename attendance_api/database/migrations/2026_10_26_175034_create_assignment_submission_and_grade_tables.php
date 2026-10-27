<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignUlid('author_id')->constrained('users')->restrictOnDelete();
            $t->string('title', 200);
            $t->text('instructions')->nullable();
            $t->dateTime('due_at')->nullable();
            $t->boolean('allow_late');
            $t->boolean('allow_resubmit');
            $t->string('state', 20)->default('draft');
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
            $t->index(['class_id', 'state', 'due_at', 'id']);
        });
        Schema::create('stored_files', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignUlid('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->foreignUlid('assignment_id')->nullable()->constrained('assignments')->restrictOnDelete();
            $t->string('purpose', 30);
            $t->string('storage_key')->unique();
            $t->string('original_name', 200);
            $t->string('mime_type', 120);
            $t->unsignedBigInteger('size_bytes');
            $t->char('sha256', 64);
            $t->string('scan_state', 30);
            $t->string('state', 20)->default('staged');
            $t->timestamps();
            $t->index(['uploaded_by', 'state', 'id']);
        });
        Schema::create('assignment_files', function (Blueprint $t) {
            $t->foreignUlid('assignment_id')->constrained('assignments')->restrictOnDelete();
            $t->foreignUlid('file_id')->constrained('stored_files')->restrictOnDelete();
            $t->primary(['assignment_id', 'file_id']);
        });
        Schema::create('submissions', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('assignment_id')->constrained('assignments')->restrictOnDelete();
            $t->foreignUlid('student_id')->constrained('users')->restrictOnDelete();
            $t->ulid('latest_revision_id')->nullable();
            $t->ulid('published_grade_publication_id')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
            $t->unique(['assignment_id', 'student_id']);
        });
        Schema::create('submission_revisions', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('submission_id')->constrained('submissions')->restrictOnDelete();
            $t->unsignedInteger('revision_no');
            $t->uuid('idempotency_key');
            $t->char('request_hash', 64);
            $t->text('note')->nullable();
            $t->dateTime('submitted_at');
            $t->boolean('is_late');
            $t->timestamps();
            $t->unique(['submission_id', 'revision_no']);
            $t->unique(['submission_id', 'idempotency_key']);
        });
        Schema::create('submission_files', function (Blueprint $t) {
            $t->foreignUlid('revision_id')->constrained('submission_revisions')->restrictOnDelete();
            $t->foreignUlid('file_id')->constrained('stored_files')->restrictOnDelete();
            $t->primary(['revision_id', 'file_id']);
        });
        Schema::create('grades', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('revision_id')->unique()->constrained('submission_revisions')->restrictOnDelete();
            $t->foreignUlid('graded_by')->constrained('users')->restrictOnDelete();
            $t->decimal('score', 4, 2);
            $t->text('feedback')->nullable();
            $t->string('state', 20)->default('draft');
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
        });
        Schema::create('grade_publications', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('grade_id')->constrained('grades')->restrictOnDelete();
            $t->unsignedInteger('grade_version');
            $t->foreignUlid('submission_id')->constrained('submissions')->restrictOnDelete();
            $t->foreignUlid('assignment_id')->constrained('assignments')->restrictOnDelete();
            $t->foreignUlid('revision_id')->constrained('submission_revisions')->restrictOnDelete();
            $t->foreignUlid('student_id')->constrained('users')->restrictOnDelete();
            $t->foreignUlid('published_by')->constrained('users')->restrictOnDelete();
            $t->decimal('score', 4, 2);
            $t->text('feedback')->nullable();
            $t->dateTime('published_at');
            $t->timestamps();
            $t->unique(['grade_id', 'grade_version']);
            $t->index(['student_id', 'assignment_id']);
        });
        Schema::create('grade_events', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->foreignUlid('submission_id')->constrained('submissions')->restrictOnDelete();
            $t->foreignUlid('actor_id')->constrained('users')->restrictOnDelete();
            $t->ulid('before_publication_id')->nullable();
            $t->foreignUlid('after_publication_id')->constrained('grade_publications')->restrictOnDelete();
            $t->string('reason', 500)->nullable();
            $t->timestamps();
        });
        Schema::table('submissions', function (Blueprint $t) {
            $t->foreign('latest_revision_id')->references('id')->on('submission_revisions')->restrictOnDelete();
            $t->foreign('published_grade_publication_id')->references('id')->on('grade_publications')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $t) {
            $t->dropForeign(['latest_revision_id']);
            $t->dropForeign(['published_grade_publication_id']);
        });
        foreach (['grade_events', 'grade_publications', 'grades', 'submission_files', 'submission_revisions', 'submissions', 'assignment_files', 'stored_files', 'assignments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
