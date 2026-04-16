<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_position_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('areas')->cascadeOnDelete();
            $table->unsignedBigInteger('machine_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedBigInteger('part_id')->nullable();
            $table->string('serial_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_position_parts');
    }
};
