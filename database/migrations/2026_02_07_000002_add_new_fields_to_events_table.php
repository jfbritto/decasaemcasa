<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Novos campos para o sistema de encontros
            $table->string('city')->nullable()->after('location');
            $table->string('address')->nullable()->after('city');
            $table->string('arrival_time')->nullable()->after('address');
            $table->integer('capacity')->default(0)->after('arrival_time');
            $table->integer('confirmed_count')->default(0)->after('capacity');

            // Dropar colunas legadas do antigo sistema de tickets
            $table->dropColumn(['location', 'total_tickets', 'available_tickets']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['city', 'address', 'arrival_time', 'capacity', 'confirmed_count']);

            // Restaurar colunas legadas
            $table->string('location')->after('date');
            $table->integer('total_tickets')->after('location');
            $table->integer('available_tickets')->after('total_tickets');
        });
    }
};
