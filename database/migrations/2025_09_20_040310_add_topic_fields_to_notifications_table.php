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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('target_topic')->nullable()->after('target_users'); // Topic được gửi
            $table->integer('devices_sent')->default(0)->after('total_failed'); // Số devices đã gửi
            $table->integer('devices_success')->default(0)->after('devices_sent'); // Số devices gửi thành công
            $table->integer('devices_failed')->default(0)->after('devices_success'); // Số devices gửi thất bại

            $table->index('target_topic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['target_topic']);
            $table->dropColumn(['target_topic', 'devices_sent', 'devices_success', 'devices_failed']);
        });
    }
};
