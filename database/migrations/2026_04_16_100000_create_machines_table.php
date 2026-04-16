<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('image_side')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();

            $table->index(['area_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
