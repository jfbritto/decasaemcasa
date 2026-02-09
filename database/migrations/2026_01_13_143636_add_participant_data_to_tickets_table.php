<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // 1. Identificação
            $table->integer('age')->nullable()->after('cpf');
            $table->string('city_neighborhood')->nullable()->after('age');
            $table->string('phone')->nullable()->after('city_neighborhood');
            $table->string('email')->nullable()->after('phone');
            $table->string('instagram_profile')->nullable()->after('email');
            
            // 2. Vivência cristã
            $table->enum('attends_church', ['sim', 'nao', 'em_busca'])->nullable()->after('instagram_profile');
            $table->string('church_name')->nullable()->after('attends_church');
            $table->text('motivation')->nullable()->after('church_name');
            $table->text('expectations')->nullable()->after('motivation');
            
            // 3. Convivência
            $table->boolean('understands_home_event')->nullable()->after('expectations');
            $table->boolean('commits_to_respect')->nullable()->after('understands_home_event');
            $table->enum('behavior_in_gatherings', ['tranquilo', 'comunicativo', 'discreto'])->nullable()->after('commits_to_respect');
            
            // 4. Conduta
            $table->boolean('agrees_christian_principles')->nullable()->after('behavior_in_gatherings');
            $table->boolean('agrees_follow_guidelines')->nullable()->after('agrees_christian_principles');
            $table->boolean('understands_can_be_removed')->nullable()->after('agrees_follow_guidelines');
            
            // 5. Histórico
            $table->enum('previous_problems', ['nao', 'sim'])->nullable()->after('understands_can_be_removed');
            $table->text('previous_problems_details')->nullable()->after('previous_problems');
            $table->text('important_info')->nullable()->after('previous_problems_details');
            
            // 6. Confirmação
            $table->boolean('agrees_declaration')->nullable()->after('important_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'age',
                'city_neighborhood',
                'phone',
                'email',
                'instagram_profile',
                'attends_church',
                'church_name',
                'motivation',
                'expectations',
                'understands_home_event',
                'commits_to_respect',
                'behavior_in_gatherings',
                'agrees_christian_principles',
                'agrees_follow_guidelines',
                'understands_can_be_removed',
                'previous_problems',
                'previous_problems_details',
                'important_info',
                'agrees_declaration',
            ]);
        });
    }
};
