<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serial_number_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('areas')->cascadeOnDelete();
            $table->unsignedBigInteger('machine_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedBigInteger('part_id')->nullable();
            $table->unsignedBigInteger('part_serial_number_id')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->dateTime('updatedDate');
            $table->unsignedTinyInteger('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_number_logs');
    }
};
