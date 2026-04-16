<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_reason', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained('operations');
            $table->foreignId('reason_id')->constrained('reasons');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_reason');
    }
};
