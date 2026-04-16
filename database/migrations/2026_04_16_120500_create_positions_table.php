<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('areas')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('status');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
