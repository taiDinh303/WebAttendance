<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_datasets', function (Blueprint $table) {
            $table->string('name', 80)->primary();
            $table->json('manifest');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_datasets');
    }
};
