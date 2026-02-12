<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE inscriptions MODIFY COLUMN status ENUM('pendente', 'aprovado', 'fila_de_espera', 'confirmado', 'rejeitado', 'cancelado') DEFAULT 'pendente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE inscriptions MODIFY COLUMN status ENUM('pendente', 'aprovado', 'fila_de_espera', 'confirmado') DEFAULT 'pendente'");
    }
};
