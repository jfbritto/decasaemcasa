<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            // NULL para inscrições aprovadas antes desta feature: nunca expiram automaticamente
            $table->timestamp('payment_deadline_at')->nullable()->after('approved_at');
            // Preenchido apenas pelo cron de expiração
            $table->timestamp('payment_expired_at')->nullable()->after('payment_deadline_at');
            $table->index(['status', 'payment_deadline_at'], 'inscriptions_status_payment_deadline_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropIndex('inscriptions_status_payment_deadline_idx');
            $table->dropColumn(['payment_deadline_at', 'payment_expired_at']);
        });
    }
};
