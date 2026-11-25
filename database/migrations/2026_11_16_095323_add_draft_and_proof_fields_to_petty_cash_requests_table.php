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
        Schema::table('petty_cash_requests', function (Blueprint $table) {
            $table->boolean('is_draft')->default(false)->after('late_work_request_id');
            $table->boolean('pending_proofs')->default(false)->after('is_draft');
            $table->timestamp('hod_approved_at')->nullable()->after('pending_proofs');
            $table->text('finance_note')->nullable()->after('hod_approved_at');
            $table->index('is_draft');
            $table->index('pending_proofs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petty_cash_requests', function (Blueprint $table) {
            $table->dropIndex(['is_draft']);
            $table->dropIndex(['pending_proofs']);
            $table->dropColumn(['is_draft', 'pending_proofs', 'hod_approved_at', 'finance_note']);
        });
    }
};
