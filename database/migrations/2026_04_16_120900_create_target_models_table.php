<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('areas')->cascadeOnDelete();
            $table->unsignedBigInteger('part_id')->nullable();
            $table->integer('tahun');
            $table->smallInteger('bulan');
            $table->float('mtbf')->nullable();
            $table->float('mttr')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_models');
    }
};
