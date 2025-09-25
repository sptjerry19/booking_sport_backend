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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable(); // Dữ liệu bổ sung
            $table->string('type')->default('general'); // booking, reminder, promo, etc.
            $table->json('target_users')->nullable(); // Array các user_id được gửi
            $table->integer('total_sent')->default(0);
            $table->integer('total_success')->default(0);
            $table->integer('total_failed')->default(0);
            $table->string('status')->default('pending'); // pending, sending, completed, failed
            $table->text('error_details')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
