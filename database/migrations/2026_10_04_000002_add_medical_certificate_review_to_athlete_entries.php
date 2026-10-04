<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('athlete_entries', function (Blueprint $table) {
            $table->string('medical_certificate_status', 20)->default('not_required')->after('status')->index();
            $table->foreignId('medical_certificate_reviewed_by')->nullable()->after('medical_certificate_status')->constrained('users')->nullOnDelete();
            $table->timestamp('medical_certificate_reviewed_at')->nullable()->after('medical_certificate_reviewed_by');
            $table->text('medical_certificate_notes')->nullable()->after('medical_certificate_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('athlete_entries', function (Blueprint $table) {
            $table->dropForeign(['medical_certificate_reviewed_by']);
            $table->dropColumn([
                'medical_certificate_status',
                'medical_certificate_reviewed_by',
                'medical_certificate_reviewed_at',
                'medical_certificate_notes',
            ]);
        });
    }
};
