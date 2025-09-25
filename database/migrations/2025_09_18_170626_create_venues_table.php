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
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->json('amenities')->nullable(); // ['parking', 'shower', 'locker', etc.]
            $table->json('images')->nullable(); // Array of image URLs
            $table->time('opening_time')->default('06:00:00');
            $table->time('closing_time')->default('22:00:00');
            $table->enum('status', ['active', 'inactive', 'pending_approval'])->default('pending_approval');
            $table->timestamps();

            $table->index(['owner_id', 'status']);
            $table->index(['latitude', 'longitude']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
