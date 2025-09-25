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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., 'Weekday Morning', 'Weekend Peak'
            $table->json('days_of_week'); // [1,2,3,4,5] for Mon-Fri
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price_per_hour', 10, 2);
            $table->integer('slot_duration_minutes')->default(60); // 60, 90, 120 minutes
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(1); // Higher priority rules override lower ones
            $table->timestamps();

            $table->index(['court_id', 'is_active', 'priority']);
            $table->index(['valid_from', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
