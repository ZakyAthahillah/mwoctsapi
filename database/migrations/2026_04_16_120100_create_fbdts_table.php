<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fbdts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('areas')->cascadeOnDelete();
            $table->integer('tahun');
            $table->smallInteger('bulan');
            $table->float('fb')->nullable();
            $table->float('dt')->nullable();
            $table->float('mttr')->nullable();
            $table->float('mtbf')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fbdts');
    }
};
