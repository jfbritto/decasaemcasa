<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Os avisos do app (aba "Avisos", spec 2026-11-08-app-mobile-design, AM41 a
 * AM46): um por e-mail enviado ao participante, gravado mesmo para quem ainda
 * não tem o app (ao entrar, a pessoa já encontra o histórico). Ligados ao
 * e-mail, como as inscrições, e comparados em binário (AM2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('email')->collation('utf8mb4_bin');
            $table->foreignId('inscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 40);
            $table->string('title');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_notifications');
    }
};
