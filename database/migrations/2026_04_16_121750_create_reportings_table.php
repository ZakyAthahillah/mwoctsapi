<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas');
            $table->string('reporting_number', 15);
            $table->foreignId('machine_id')->constrained('machines');
            $table->foreignId('position_id')->constrained('positions');
            $table->foreignId('part_id')->constrained('parts');
            $table->foreignId('division_id')->constrained('divisions');
            $table->foreignId('operation_id')->constrained('operations');
            $table->foreignId('reason_id')->constrained('reasons');
            $table->text('reporting_notes')->nullable();
            $table->foreignId('informant_id')->constrained('informants');
            $table->foreignId('shift_id_reporting')->constrained('shifts');
            $table->foreignId('shift_id_start')->nullable()->constrained('shifts');
            $table->foreignId('shift_id_finish')->nullable()->constrained('shifts');
            $table->dateTime('reporting_date');
            $table->dateTime('processing_date_start')->nullable();
            $table->dateTime('processing_date_finish')->nullable();
            $table->time('gap_time_response')->nullable();
            $table->time('total_time_finishing')->nullable();
            $table->integer('sort_order');
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('status');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unsignedTinyInteger('reporting_type')->default(1);
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('informants');
            $table->foreignId('shift_id_approved')->nullable()->constrained('shifts');
            $table->time('total_time_approved')->nullable();
            $table->time('execution_time')->nullable();
            $table->unsignedTinyInteger('is_updated')->default(0);
            $table->foreignId('part_serial_number_id')->nullable()->constrained('part_serial_numbers');
            $table->text('approved_notes')->nullable();
            $table->foreignId('part_serial_number_id_new')->nullable()->constrained('part_serial_numbers');
            $table->foreignId('operation_id_actual')->nullable()->constrained('operations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportings');
    }
};
