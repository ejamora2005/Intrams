<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\IntramuralEdition;
use App\Models\Student;
use App\Models\Team;
use App\Services\EditionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestIntramuralsDataSeeder extends Seeder
{
    public function run(): void
    {
        Student::query()
            ->where('student_number', 'like', 'TEST-%')
            ->delete();

        $start = today();
        $edition = IntramuralEdition::updateOrCreate(
            ['name' => '2026 SLSU Bontoc-campus Intramurals', 'school_year' => '2026-2027'],
            ['starts_on' => $start, 'ends_on' => $start->copy()->addDays(5), 'status' => 'active'],
        );
        app(EditionService::class)->syncDefaultSports($edition);

        $defaultTeams = [
            ['name' => 'Mighty Sea Dragons', 'department' => 'Marine Biology', 'course_code' => 'MB'],
            ['name' => 'Terraquatic Eagles', 'department' => 'Fisheries & Agriculture', 'course_code' => 'FA'],
            ['name' => 'Trojan Warriors', 'department' => 'Information Technology', 'course_code' => 'IT'],
        ];

        foreach ($defaultTeams as $defaultTeam) {
            $course = Course::query()->updateOrCreate(
                ['code' => $defaultTeam['course_code']],
                ['name' => $defaultTeam['department'], 'status' => 'active'],
            );

            $team = Team::withTrashed()->updateOrCreate(
                ['edition_id' => $edition->id, 'code' => Str::slug($defaultTeam['name'])],
                [
                    'course_id' => $course->id,
                    'name' => $defaultTeam['name'],
                    'description' => $defaultTeam['department'].' default intramurals team.',
                    'status' => 'active',
                ],
            );

            if ($team->trashed()) {
                $team->restore();
            }
        }
    }
}
