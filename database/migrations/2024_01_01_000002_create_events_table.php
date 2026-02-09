<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->dateTime('date');
            $table->string('location');
            $table->integer('total_tickets');
            $table->integer('available_tickets');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['draft', 'published', 'cancelled', 'finished'])->default('draft');
            $table->text('terms')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

