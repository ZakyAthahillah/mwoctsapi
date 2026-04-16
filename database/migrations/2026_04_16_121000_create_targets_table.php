<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('areas')->cascadeOnDelete();
            $table->integer('tahun');
            $table->smallInteger('bulan');
            $table->float('mtbf')->nullable();
            $table->float('mttr')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unsignedBigInteger('machine_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedBigInteger('part_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
