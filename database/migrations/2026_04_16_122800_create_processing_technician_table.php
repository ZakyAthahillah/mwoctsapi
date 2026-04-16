<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processing_technician', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processing_id')->constrained('processings');
            $table->foreignId('technician_id')->constrained('technicians');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_technician');
    }
};
