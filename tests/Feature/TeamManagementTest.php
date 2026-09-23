<?php

use App\Models\IntramuralEdition;
use App\Models\Course;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function teamModuleAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'status' => 'active']);
}

function teamModuleEdition(array $overrides = []): IntramuralEdition
{
    return IntramuralEdition::query()->create(array_merge([
        'name' => 'Intramurals 2026',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-31',
        'status' => 'draft',
    ], $overrides));
}

function teamModuleStudent(array $overrides = []): Student
{
    return Student::factory()->create(array_merge(['status' => 'active'], $overrides));
}

function validTeamData(IntramuralEdition $edition, array $overrides = []): array
{
    return array_merge([
        'edition_id' => $edition->id,
        'name' => 'Blue Sharks',
        'code' => 'BLUE',
        'description' => 'Blue house athletes.',
        'status' => 'active',
    ], $overrides);
}

test('admin can create an edition-specific team and the action is audited', function () {
    $admin = teamModuleAdmin();
    $edition = teamModuleEdition();

    $this->actingAs($admin)->post(route('admin.teams.store'), validTeamData($edition))
        ->assertRedirect(route('admin.teams.index'));

    $this->assertDatabaseHas('teams', ['edition_id' => $edition->id, 'name' => 'Blue Sharks', 'code' => 'BLUE', 'status' => 'active']);
    $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'team.created', 'outcome' => 'success']);
});

test('course students are added when a course team is created or later selected', function () {
    $admin = teamModuleAdmin();
    $edition = teamModuleEdition();
    $course = Course::query()->create(['name' => 'Bachelor of Science in Information Technology', 'code' => 'BSIT', 'status' => 'active']);
    $firstStudent = teamModuleStudent(['course_id' => $course->id]);
    $otherStudent = teamModuleStudent();

    $this->actingAs($admin)->post(route('admin.teams.store'), validTeamData($edition, ['name' => 'IT TEAM', 'code' => 'ITTEAM', 'course_id' => $course->id]))
        ->assertRedirect(route('admin.teams.index'));

    $courseTeam = Team::query()->where('code', 'ITTEAM')->firstOrFail();
    $this->assertDatabaseHas('team_members', ['team_id' => $courseTeam->id, 'student_id' => $firstStudent->id]);
    $this->assertDatabaseMissing('team_members', ['team_id' => $courseTeam->id, 'student_id' => $otherStudent->id]);

    $secondCourse = Course::query()->create(['name' => 'Bachelor of Science in Accountancy', 'code' => 'BSA', 'status' => 'active']);
    $secondStudent = teamModuleStudent(['course_id' => $secondCourse->id]);
    $manualTeam = Team::query()->create(validTeamData($edition, ['name' => 'ACCOUNTING TEAM', 'code' => 'ACCTTEAM']));

    $this->actingAs($admin)->put(route('admin.teams.update', $manualTeam), validTeamData($edition, ['name' => 'ACCOUNTING TEAM', 'code' => 'ACCTTEAM', 'course_id' => $secondCourse->id]))
        ->assertRedirect(route('admin.teams.index'));

    $this->assertDatabaseHas('team_members', ['team_id' => $manualTeam->id, 'student_id' => $secondStudent->id]);
});

test('team code must be unique within an edition', function () {
    $admin = teamModuleAdmin();
    $edition = teamModuleEdition();
    Team::query()->create(validTeamData($edition));

    $this->actingAs($admin)->from(route('admin.teams.create'))
        ->post(route('admin.teams.store'), validTeamData($edition, ['name' => 'Blue Eagles']))
        ->assertRedirect(route('admin.teams.create'))
        ->assertSessionHasErrors('code');

    $otherEdition = teamModuleEdition(['name' => 'Intramurals 2027', 'school_year' => '2027-2028']);
    $response = $this->actingAs($admin)->post(route('admin.teams.store'), validTeamData($otherEdition));
    $response->assertRedirect(route('admin.teams.index'));
});

test('admin can assign and remove an athlete from a team roster only once per edition', function () {
    $admin = teamModuleAdmin();
    $edition = teamModuleEdition();
    $team = Team::query()->create(validTeamData($edition));
    $otherTeam = Team::query()->create(validTeamData($edition, ['name' => 'Gold Eagles', 'code' => 'GOLD']));
    $student = teamModuleStudent();

    $this->actingAs($admin)->post(route('admin.teams.members.store', $team), ['student_ids' => [$student->id]])
        ->assertRedirect(route('admin.teams.index'));
    $this->assertDatabaseHas('team_members', ['edition_id' => $edition->id, 'team_id' => $team->id, 'student_id' => $student->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'team.member.assigned']);

    $this->actingAs($admin)->post(route('admin.teams.members.store', $otherTeam), ['student_ids' => [$student->id]])
        ->assertStatus(422);

    $memberId = (int) DB::table('team_members')->where('student_id', $student->id)->value('id');
    $this->actingAs($admin)->delete(route('admin.teams.members.destroy', [$team, $memberId]))
        ->assertRedirect(route('admin.teams.index'));
    $this->assertDatabaseMissing('team_members', ['id' => $memberId]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'team.member.removed']);
});

test('admin can filter and bulk-remove selected team roster members', function () {
    $admin = teamModuleAdmin();
    $edition = teamModuleEdition();
    $course = Course::query()->create(['name' => 'Bachelor of Science in Information Technology', 'code' => 'BSIT', 'status' => 'active']);
    $team = Team::query()->create(validTeamData($edition));
    $firstStudent = teamModuleStudent(['course_id' => $course->id, 'first_name' => 'Remove', 'last_name' => 'First']);
    $secondStudent = teamModuleStudent(['course_id' => $course->id, 'first_name' => 'Remove', 'last_name' => 'Second']);
    $remainingStudent = teamModuleStudent(['first_name' => 'Keep', 'last_name' => 'Member']);

    $this->actingAs($admin)->post(route('admin.teams.members.store', $team), ['student_ids' => [$firstStudent->id, $secondStudent->id, $remainingStudent->id]])
        ->assertRedirect(route('admin.teams.index'));

    $this->actingAs($admin)->get(route('admin.teams.edit', [$team, 'roster_course_id' => $course->id, 'roster_search' => 'Remove']))
        ->assertOk()
        ->assertSee($firstStudent->full_name)
        ->assertSee($secondStudent->full_name)
        ->assertDontSee($remainingStudent->full_name);

    $memberIds = DB::table('team_members')->where('team_id', $team->id)->whereIn('student_id', [$firstStudent->id, $secondStudent->id])->pluck('id')->all();
    $this->actingAs($admin)->post(route('admin.teams.members.bulk-remove', $team), ['member_ids' => $memberIds])
        ->assertRedirect(route('admin.teams.index'));

    $this->assertDatabaseMissing('team_members', ['team_id' => $team->id, 'student_id' => $firstStudent->id]);
    $this->assertDatabaseMissing('team_members', ['team_id' => $team->id, 'student_id' => $secondStudent->id]);
    $this->assertDatabaseHas('team_members', ['team_id' => $team->id, 'student_id' => $remainingStudent->id]);
    expect(DB::table('audit_logs')->where('action', 'team.member.removed')->count())->toBe(2);
});

test('inactive teams cannot receive athletes and teams can be archived and restored', function () {
    $admin = teamModuleAdmin();
    $edition = teamModuleEdition();
    $team = Team::query()->create(validTeamData($edition, ['status' => 'inactive']));
    $student = teamModuleStudent();

    $this->actingAs($admin)->post(route('admin.teams.members.store', $team), ['student_ids' => [$student->id]])->assertStatus(422);
    $this->actingAs($admin)->delete(route('admin.teams.destroy', $team))->assertRedirect(route('admin.teams.index'));
    $this->assertSoftDeleted('teams', ['id' => $team->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'team.archived']);

    $this->actingAs($admin)->post(route('admin.teams.restore', $team->id))->assertRedirect(route('admin.teams.index', ['status' => 'archived']));
    $this->assertDatabaseHas('teams', ['id' => $team->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'team.restored']);
});

test('coordinators cannot manage teams', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);
    $edition = teamModuleEdition();

    $this->actingAs($coordinator)->get(route('admin.teams.index'))->assertForbidden();
    $this->actingAs($coordinator)->post(route('admin.teams.store'), validTeamData($edition))->assertForbidden();
});
