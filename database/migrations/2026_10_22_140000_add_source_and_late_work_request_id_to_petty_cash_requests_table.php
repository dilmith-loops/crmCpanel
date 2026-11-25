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
            $table->string('source')->nullable()->after('reappeal_count'); // e.g. 'work_tracker'
            $table->unsignedBigInteger('late_work_request_id')->nullable()->after('source');
            $table->foreign('late_work_request_id')->references('id')->on('late_work_requests')->nullOnDelete();
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petty_cash_requests', function (Blueprint $table) {
            $table->dropForeign(['late_work_request_id']);
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'late_work_request_id']);
        });
    }
};
