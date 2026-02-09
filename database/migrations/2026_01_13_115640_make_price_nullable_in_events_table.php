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
        // Tornar o campo price nullable e definir valor padrão 0
        DB::statement('ALTER TABLE `events` MODIFY COLUMN `price` DECIMAL(10, 2) DEFAULT 0 NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para NOT NULL sem valor padrão (comportamento original)
        DB::statement('ALTER TABLE `events` MODIFY COLUMN `price` DECIMAL(10, 2) NOT NULL');
    }
};
