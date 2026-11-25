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
            $table->text('job_number')->nullable()->change();
            $table->text('job_label')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('late_work_requests', function (Blueprint $table) {
            $table->string('job_number', 100)->nullable(false)->change();
            $table->string('job_label', 255)->nullable()->change();
        });
    }
};
