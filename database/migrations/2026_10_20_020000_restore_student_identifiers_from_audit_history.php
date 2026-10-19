<?php

use App\Models\Student;
use App\Services\AuditService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        DB::transaction(function (): void {
            $students = Student::withTrashed()->where(function ($query): void {
                $query->whereNull('student_number')->orWhere('student_number', '');
            })->lockForUpdate()->get();

            foreach ($students as $student) {
                $history = DB::table('audit_logs')->where('auditable_type', Student::class)
                    ->where('auditable_id', $student->id)->where('action', 'student.updated')
                    ->orderByDesc('id')->cursor();
                foreach ($history as $record) {
                    $before = json_decode($record->old_values ?? '', true);
                    $identifier = $before['student_number'] ?? null;
                    if (! is_string($identifier) || trim($identifier) === '' || strlen($identifier) > 255
                        || Student::withTrashed()->where('student_number', $identifier)->whereKeyNot($student->id)->exists()) {
                        continue;
                    }

                    $previous = $student->student_number;
                    // Restore only the verified identifier, without replacing
                    // names, profiles, sports, memberships, or competition data.
                    $student->student_number = $identifier;
                    $student->timestamps = false;
                    $student->saveQuietly();
                    app(AuditService::class)->record('student.identifier_restored', $student,
                        ['student_number' => $previous], ['student_number' => $identifier, 'source_audit_id' => $record->id]);
                    break;
                }
            }
        });
    }

    public function down(): void
    {
        // Corrected identifiers must not be erased again on rollback.
    }
};
