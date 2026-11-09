<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabelas do app do participante (spec 2026-11-08-app-mobile-design, AM1 a AM8
 * e AM20). Nenhuma tabela existente muda.
 *
 * - participants: a conta do app. A chave é o e-mail (minúsculo); as
 *   inscrições do participante são as que têm o mesmo e-mail (AM2).
 * - participant_login_codes: o código de 6 dígitos do login, só como hash,
 *   um vivo por e-mail (AM3 e AM4).
 * - participant_push_tokens: um aparelho por linha (token Expo), único no
 *   sistema inteiro: registrado em outra conta, muda de dono (AM20).
 */
return new class extends Migration
{
    public function up(): void
    {
        // O e-mail é comparado em binário (utf8mb4_bin): na collation padrão
        // (utf8mb4_unicode_ci) "maria@gmáil.com" seria igual a
        // "maria@gmail.com", e quem tivesse o código do domínio parecido
        // entraria na conta de outra pessoa. O e-mail chega aqui sempre
        // aparado e minúsculo (Participant::normalizeEmail).
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('email')->collation('utf8mb4_bin')->unique();
            $table->string('name')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('participant_login_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->collation('utf8mb4_bin')->index();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('participant_push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform', 10);
            $table->string('device_name', 120)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_push_tokens');
        Schema::dropIfExists('participant_login_codes');
        Schema::dropIfExists('participants');
    }
};
