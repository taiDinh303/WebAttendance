<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_preferences', function (Blueprint $table) {
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('key', 80);
            $table->longText('value');
            $table->unsignedInteger('version')->default(1);
            $table->primary(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_preferences');
    }
};
