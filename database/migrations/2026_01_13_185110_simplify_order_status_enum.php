<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Simplificar o ENUM para apenas os status relevantes: pending, confirmed, cancelled
        // Primeiro, atualizar registros com status inválidos
        DB::table('orders')
            ->whereIn('status', ['processing', 'paid', 'refunded'])
            ->update(['status' => 'pending']);

        // Modificar o ENUM
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Voltar ao ENUM anterior
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'processing', 'paid', 'confirmed', 'cancelled', 'refunded') DEFAULT 'pending'");
    }
};
