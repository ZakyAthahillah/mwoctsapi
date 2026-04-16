<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_reason', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reason_id')->constrained('reasons');
            $table->foreignId('part_id')->constrained('parts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_reason');
    }
};
