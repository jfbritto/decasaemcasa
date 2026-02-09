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
        // Modificar o ENUM para incluir 'confirmed'
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'processing', 'paid', 'confirmed', 'cancelled', 'refunded') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover 'confirmed' do ENUM (voltar ao estado anterior)
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'processing', 'paid', 'cancelled', 'refunded') DEFAULT 'pending'");
    }
};
