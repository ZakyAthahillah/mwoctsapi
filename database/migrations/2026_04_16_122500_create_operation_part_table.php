<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_part', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts');
            $table->foreignId('operation_id')->constrained('operations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_part');
    }
};
