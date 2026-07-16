<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            // Janela aplicada quando o prazo foi iniciado (60, 360, ...);
            // NULL para prazos anteriores a esta regra (label cai no default)
            $table->unsignedSmallInteger('payment_deadline_minutes')->nullable()->after('payment_deadline_at');
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropColumn('payment_deadline_minutes');
        });
    }
};
