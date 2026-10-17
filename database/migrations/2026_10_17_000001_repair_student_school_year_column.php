<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Some deployments recorded the original school-year migration before
        // the column was actually created. Keep student edit requests usable.
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'school_year')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->string('school_year', 20)->nullable()->after('last_name')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'school_year')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->dropIndex(['school_year']);
                $table->dropColumn('school_year');
            });
        }
    }
};
