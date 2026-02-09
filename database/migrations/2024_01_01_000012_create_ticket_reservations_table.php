<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable();
            $table->integer('quantity');
            $table->string('reservation_token')->unique();
            $table->timestamp('expires_at');
            $table->enum('status', ['pending', 'confirmed', 'expired', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index('expires_at');
            $table->index('reservation_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_reservations');
    }
};
