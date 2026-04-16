<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tat_tpds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('areas')->cascadeOnDelete();
            $table->date('tanggal');
            $table->float('tat')->nullable();
            $table->float('tpd')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tat_tpds');
    }
};
