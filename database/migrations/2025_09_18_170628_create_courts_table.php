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
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->foreignId('sport_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable(); // Court identifier like 'A1', 'B2'
            $table->text('description')->nullable();
            $table->enum('surface_type', ['grass', 'concrete', 'wood', 'synthetic', 'clay', 'other'])->nullable();
            $table->json('dimensions')->nullable(); // {length: 40, width: 20, unit: 'm'}
            $table->decimal('hourly_rate', 10, 2)->default(0); // Base rate, can be overridden by pricing rules
            $table->boolean('is_active')->default(true);
            $table->json('images')->nullable();
            $table->timestamps();

            $table->index(['venue_id', 'sport_id', 'is_active']);
            $table->unique(['venue_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
