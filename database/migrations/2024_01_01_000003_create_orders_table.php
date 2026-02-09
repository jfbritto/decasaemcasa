<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->integer('quantity');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'processing', 'paid', 'cancelled', 'refunded'])->default('pending');
            $table->string('payment_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->json('payment_data')->nullable();
            $table->string('reservation_token')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

