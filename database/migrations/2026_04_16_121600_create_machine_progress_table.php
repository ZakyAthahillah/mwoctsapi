<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines');
            $table->unsignedTinyInteger('data')->default(0);
            $table->unsignedTinyInteger('position')->default(0);
            $table->unsignedTinyInteger('operation')->default(0);
            $table->unsignedTinyInteger('reason')->default(0);
            $table->unsignedTinyInteger('image')->default(0);
            $table->unsignedTinyInteger('part')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_progress');
    }
};
