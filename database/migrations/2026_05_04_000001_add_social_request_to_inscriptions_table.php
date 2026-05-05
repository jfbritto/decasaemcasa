<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->enum('social_request_status', ['pendente', 'aprovado', 'rejeitado'])->nullable()->after('contribution_amount');
            $table->text('social_request_reason')->nullable()->after('social_request_status');
            $table->decimal('social_request_amount', 10, 2)->nullable()->after('social_request_reason');
            $table->text('social_request_admin_message')->nullable()->after('social_request_amount');
            $table->timestamp('social_request_submitted_at')->nullable()->after('social_request_admin_message');
            $table->timestamp('social_request_reviewed_at')->nullable()->after('social_request_submitted_at');
            $table->unsignedBigInteger('social_request_reviewed_by')->nullable()->after('social_request_reviewed_at');

            $table->foreign('social_request_reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropForeign(['social_request_reviewed_by']);
            $table->dropColumn([
                'social_request_status',
                'social_request_reason',
                'social_request_amount',
                'social_request_admin_message',
                'social_request_submitted_at',
                'social_request_reviewed_at',
                'social_request_reviewed_by',
            ]);
        });
    }
};
