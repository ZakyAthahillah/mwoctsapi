<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas');
            $table->unsignedBigInteger('reporting_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->dateTime('processing_date_start');
            $table->dateTime('processing_date_finish')->nullable();
            $table->foreignId('shift_id_start')->constrained('informants');
            $table->foreignId('shift_id_finish')->nullable()->constrained('informants');
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('is_active')->default(1);
            $table->unsignedTinyInteger('status');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unsignedBigInteger('part_serial_number_id_new')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processings');
    }
};
