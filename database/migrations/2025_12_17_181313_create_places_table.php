<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('area');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->enum('price', ['budget', 'moderate', 'expensive']);
            $table->json('tags');
            $table->boolean('is_halal')->default(false);
            $table->string('cuisine_type')->nullable();
            $table->string('opening_hours')->nullable();
            $table->timestamps();

            $table->index(['area', 'price']);
            $table->index('is_halal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
