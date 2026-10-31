<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcement_comments', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->after('hidden');
        });
        Schema::create('announcement_comment_audits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('comment_id')->constrained('announcement_comments')->restrictOnDelete();
            $table->foreignUlid('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 20);
            $table->text('before_data')->nullable();
            $table->text('after_data')->nullable();
            $table->timestamps();
            $table->index(['comment_id', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_comment_audits');
        Schema::table('announcement_comments', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
};
