<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('code', 10);
            $table->string('name', 100);
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedBigInteger('group_id')->nullable();
            $table->timestamps();

            $table->index(['area_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informants');
    }
};
