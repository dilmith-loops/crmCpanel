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
        Schema::table('late_work_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('late_work_requests', 'is_wfh')) {
                $table->boolean('is_wfh')->default(false)->after('is_holiday');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('late_work_requests', function (Blueprint $table) {
            if (Schema::hasColumn('late_work_requests', 'is_wfh')) {
                $table->dropColumn('is_wfh');
            }
        });
    }
};
