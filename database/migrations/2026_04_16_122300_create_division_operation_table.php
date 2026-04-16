<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('division_operation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained('operations');
            $table->foreignId('division_id')->constrained('divisions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('division_operation');
    }
};
