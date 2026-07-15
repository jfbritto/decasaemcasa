<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índice para o guard do cron de expiração, que consulta notifications
     * por (channel, status) numa tabela que só cresce — sem índice é full-scan.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['channel', 'status'], 'notifications_channel_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_channel_status_idx');
        });
    }
};
