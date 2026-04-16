<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('areas')->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name', 255);
            $table->foreignId('division_id')->nullable()->constrained('divisions');
            $table->unsignedTinyInteger('status');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reasons');
    }
};
