<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('token', 64)->unique();

            // Dados do participante
            $table->string('full_name');
            $table->string('cpf', 14);
            $table->date('birth_date');
            $table->string('city_neighborhood');
            $table->string('whatsapp', 20);
            $table->string('email');
            $table->string('instagram')->nullable();
            $table->text('motivation');

            // Termos e status
            $table->boolean('terms_accepted')->default(false);
            $table->enum('status', ['pendente', 'aprovado', 'fila_de_espera', 'confirmado'])->default('pendente');

            // Pagamento (comprovante)
            $table->string('payment_proof')->nullable();

            // Admin
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['event_id', 'status']);
            $table->index('email');
            $table->index('cpf');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
