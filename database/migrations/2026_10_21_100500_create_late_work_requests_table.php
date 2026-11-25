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
        if (!Schema::hasTable('late_work_requests')) {
            Schema::create('late_work_requests', function (Blueprint $table) {
                $table->id();
                $table->string('reference_number', 32)->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('department_name', 100)->nullable();
                $table->foreignId('hod_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 20)->default('late'); // 'late' or 'weekend'
                $table->date('date');
                $table->boolean('is_holiday')->default(false);
                $table->string('job_number', 100);
                $table->string('job_label', 255)->nullable();
                $table->text('reason');
                $table->string('start_time', 10)->nullable();
                $table->string('finish_time', 10);
                $table->boolean('meal')->default(false);
                $table->boolean('taxi')->default(false);
                $table->string('lieu', 20)->nullable(); // 'half', 'full', or null
                $table->string('next_day', 30)->nullable(); // 'normal', '10am', '11am', 'half', 'full'
                $table->string('status', 20)->default('pending'); // 'pending', 'approved', 'declined', 'withdrawn'
                $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('decided_at')->nullable();
                $table->text('hod_note')->nullable();
                $table->boolean('is_after_3pm')->default(false);
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['hod_id', 'status']);
                $table->index(['date', 'status']);
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('late_work_requests');
    }
};
