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
                $table->text('reason')->nullable()->change();
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
                $table->text('reason')->nullable(false)->change();
            });
        }
    }
};

