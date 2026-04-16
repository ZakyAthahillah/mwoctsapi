<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_part_sides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines');
            $table->foreignId('part_id')->constrained('parts');
            $table->integer('sort_order');
            $table->string('pos_x');
            $table->string('pos_y');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_part_sides');
    }
};
