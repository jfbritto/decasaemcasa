<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('host_applications');
    }

    public function down(): void
    {
        Schema::create('host_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('age');
            $table->string('phone');
            $table->string('email');
            $table->string('city_neighborhood');
            $table->enum('attends_church', ['sim', 'nao', 'frequento_ocasionalmente'])->nullable();
            $table->string('church_name')->nullable();
            $table->text('motivation');
            $table->text('family_meaning');
            $table->enum('residence_type', ['casa', 'apartamento', 'sitio_chacara']);
            $table->enum('residence_ownership', ['propria', 'alugada']);
            $table->integer('capacity');
            $table->enum('event_location', ['interno', 'externo', 'ambos']);
            $table->boolean('has_sufficient_outlets');
            $table->enum('noise_issue', ['nao', 'sim'])->default('nao');
            $table->string('noise_issue_details')->nullable();
            $table->boolean('has_bathrooms');
            $table->enum('easy_access', ['sim', 'nao'])->default('sim');
            $table->string('access_details')->nullable();
            $table->boolean('residents_agree');
            $table->enum('special_needs', ['nao', 'sim'])->default('nao');
            $table->string('special_needs_details')->nullable();
            $table->boolean('commits_to_follow_guidelines');
            $table->boolean('agrees_christian_principles');
            $table->boolean('agrees_can_end_event');
            $table->enum('can_send_photos', ['sim', 'nao'])->default('nao');
            $table->json('photos')->nullable();
            $table->boolean('agrees_declaration');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }
};
