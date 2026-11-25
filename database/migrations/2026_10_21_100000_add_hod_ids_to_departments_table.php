<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('departments') && !Schema::hasColumn('departments', 'hod_ids')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->json('hod_ids')->nullable()->after('status');
            });

            // Map existing HODs by department name
            $departments = DB::table('departments')->get();
            foreach ($departments as $dept) {
                $hodUserIds = User::where('role', 'HOD')
                    ->where('department', $dept->name)
                    ->pluck('id')
                    ->toArray();

                if (!empty($hodUserIds)) {
                    DB::table('departments')
                        ->where('id', $dept->id)
                        ->update(['hod_ids' => json_encode($hodUserIds)]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'hod_ids')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropColumn('hod_ids');
            });
        }
    }
};
