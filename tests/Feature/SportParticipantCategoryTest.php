<?php

use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\Student;
use App\Models\User;

function categoryAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'status' => 'active']);
}

function categoryEdition(): IntramuralEdition
{
    return IntramuralEdition::query()->create([
        'name' => 'Category Edition',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-31',
        'status' => 'active',
    ]);
}

test('an admin can set a sport participant category', function () {
    $admin = categoryAdmin();
    $edition = categoryEdition();

    $this->actingAs($admin)
        ->post(route('admin.sports.store'), [
            'edition_id' => $edition->id,
            'name' => 'Women Volleyball',
            'description' => null,
            'participant_category' => 'female',
            'status' => 'active',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('sports', [
        'name' => 'Women Volleyball',
        'participant_category' => 'female',
    ]);
});

test('female and male sport categories are enforced for direct participant assignment', function () {
    $admin = categoryAdmin();
    $edition = categoryEdition();
    $sport = Sport::query()->create([
        'name' => 'Women Chess',
        'code' => 'W-CHESS',
        'participant_category' => 'female',
        'status' => 'active',
    ]);
    $editionSport = EditionSport::query()->create([
        'edition_id' => $edition->id,
        'sport_id' => $sport->id,
        'participant_type' => 'individual',
        'game_mechanic' => 'custom',
        'status' => 'active',
    ]);
    $maleStudent = Student::factory()->create(['gender' => 'Male', 'status' => 'active']);

    $this->actingAs($admin)
        ->post(route('admin.sports.participants.store', $sport), [
            'edition_id' => $edition->id,
            'student_ids' => [$maleStudent->id],
        ])
        ->assertSessionHasErrors('student_ids');

    $this->assertDatabaseMissing('athlete_entries', [
        'edition_sport_id' => $editionSport->id,
        'student_id' => $maleStudent->id,
    ]);
});
