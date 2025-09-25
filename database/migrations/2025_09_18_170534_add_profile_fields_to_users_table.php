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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'professional'])->default('beginner')->after('phone');
            $table->json('preferred_sports')->nullable()->after('level');
            $table->json('preferred_position')->nullable()->after('preferred_sports');
            $table->string('avatar')->nullable()->after('preferred_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'level', 'preferred_sports', 'preferred_position', 'avatar']);
        });
    }
};
