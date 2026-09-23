<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\IntramuralEdition;
use App\Models\Student;
use Illuminate\Database\Seeder;

class TestIntramuralsDataSeeder extends Seeder
{
    public function run(): void
    {
        $start = today();
        IntramuralEdition::updateOrCreate(
            ['name' => '2026 SLSU Bontoc-campus Intramurals', 'school_year' => '2026-2027'],
            ['starts_on' => $start, 'ends_on' => $start->copy()->addDays(5), 'status' => 'active'],
        );

        $names = [
            'BSIT' => [['Alden', 'Cruz'], ['Bianca', 'Ramos'], ['Carlo', 'Santos'], ['Diana', 'Reyes'], ['Ethan', 'Garcia']],
            'BSFI' => [['Faith', 'Dela Cruz'], ['Gio', 'Mendoza'], ['Hannah', 'Flores'], ['Ivan', 'Torres'], ['Jessa', 'Aquino']],
            'BSA' => [['Kyle', 'Villanueva'], ['Lara', 'Castillo'], ['Marco', 'Navarro'], ['Nina', 'Bautista'], ['Oscar', 'Morales']],
            'BSMB' => [['Paula', 'Gomez'], ['Quinn', 'Salazar'], ['Rafael', 'Martinez'], ['Sofia', 'Rivera'], ['Tomas', 'Lopez']],
        ];
        $years = ['1st', '2nd', '3rd', '4th'];
        $sections = ['A', 'B', 'C'];
        $genders = ['male', 'female', 'lgbtq+'];

        foreach ($names as $courseCode => $students) {
            $course = Course::updateOrCreate(['code' => $courseCode], ['name' => $courseCode, 'status' => 'active']);
            foreach ($students as $position => [$firstName, $lastName]) {
                Student::updateOrCreate(
                    ['student_number' => "TEST-{$courseCode}-".str_pad((string) ($position + 1), 2, '0', STR_PAD_LEFT)],
                    ['first_name' => $firstName, 'last_name' => $lastName, 'course_id' => $course->id, 'school_year' => '2026-2027', 'year_level' => $years[array_rand($years)], 'section' => $sections[array_rand($sections)], 'gender' => $genders[array_rand($genders)], 'status' => 'active'],
                );
            }
        }
    }
}
