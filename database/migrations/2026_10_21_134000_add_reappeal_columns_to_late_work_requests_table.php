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
        if (Schema::hasTable('late_work_requests')) {
            Schema::table('late_work_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('late_work_requests', 'reappeal_notes')) {
                    $table->text('reappeal_notes')->nullable()->after('hod_note');
                }
                if (!Schema::hasColumn('late_work_requests', 'reappealed_at')) {
                    $table->timestamp('reappealed_at')->nullable()->after('reappeal_notes');
                }
                if (!Schema::hasColumn('late_work_requests', 'reappeal_count')) {
                    $table->unsignedInteger('reappeal_count')->default(0)->after('reappealed_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('late_work_requests')) {
            Schema::table('late_work_requests', function (Blueprint $table) {
                $columns = ['reappeal_notes', 'reappealed_at', 'reappeal_count'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('late_work_requests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
