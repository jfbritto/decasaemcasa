<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // mercadopago, stripe, pagseguro, etc
            $table->string('display_name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('credentials'); // Chaves e tokens específicos do gateway
            $table->json('settings')->nullable(); // Configurações adicionais
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};

