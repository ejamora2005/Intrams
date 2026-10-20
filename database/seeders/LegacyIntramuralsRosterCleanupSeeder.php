<?php

namespace Database\Seeders;

use App\Models\AthleteEntry;
use App\Models\Student;
use App\Models\TeamMember;
use Database\Seeders\Concerns\SkipsProductionRosterImports;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Remove the retired 17-record sample roster from existing databases. */
class LegacyIntramuralsRosterCleanupSeeder extends Seeder
{
    use SkipsProductionRosterImports;

    private const LEGACY_STUDENT_NUMBERS = [
        '2310042-2', '2310007-2', '2310060-1', '2310016-2', '2310067-2',
        '2310025-2', '2310072-1', '2310106-2', '2310005-2', '2310050-2',
        '2310036-2', '2310002-2', '2310099-1', '2310038-2', '2310045-2',
        '2310018-1', '2310047-2',
    ];

    public function run(): void
    {
        if ($this->skipsProductionRosterImport()) {
            return;
        }

        DB::transaction(function (): void {
            $studentIds = Student::withTrashed()
                ->whereIn('student_number', self::LEGACY_STUDENT_NUMBERS)
                ->pluck('id');

            if ($studentIds->isEmpty()) {
                return;
            }

            $athleteEntryIds = AthleteEntry::query()
                ->whereIn('student_id', $studentIds)
                ->pluck('id');

            if (Schema::hasTable('schedule_participants')) {
                DB::table('schedule_participants')->whereIn('athlete_entry_id', $athleteEntryIds)->delete();
            }
            if (Schema::hasTable('event_registrations')) {
                DB::table('event_registrations')->whereIn('student_id', $studentIds)->delete();
            }
            AthleteEntry::query()->whereIn('id', $athleteEntryIds)->delete();
            TeamMember::query()->whereIn('student_id', $studentIds)->delete();
            Student::withTrashed()->whereIn('id', $studentIds)->forceDelete();
        });

        $this->command?->info('Retired legacy roster records removed.');
    }
}
