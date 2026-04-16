<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('areas')->cascadeOnDelete();
            $table->unsignedBigInteger('part_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_serial_numbers');
    }
};
