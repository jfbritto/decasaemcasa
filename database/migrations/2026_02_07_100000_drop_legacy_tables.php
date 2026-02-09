<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dropar todas as tabelas legadas do antigo sistema de tickets/pagamentos.
     */
    public function up(): void
    {
        // Dropar tabelas com FK primeiro (ordem de dependência)
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('payment_logs');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_reservations');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('analytics_events');
    }

    /**
     * Não há reversão — as tabelas legadas não devem ser recriadas.
     */
    public function down(): void
    {
        // Irreversível: as tabelas legadas foram removidas permanentemente.
    }
};
