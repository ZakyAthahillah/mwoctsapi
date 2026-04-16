<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_position', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('positions');
            $table->foreignId('machine_id')->constrained('machines');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_position');
    }
};
