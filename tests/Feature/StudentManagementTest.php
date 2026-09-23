<?php

use App\Models\Student;
use App\Models\User;

function activeAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'status' => 'active']);
}

function validStudentData(array $overrides = []): array
{
    return array_merge([
        'student_number' => '2026-00001',
        'first_name' => 'Ana',
        'middle_name' => 'Santos',
        'last_name' => 'Reyes',
        'school_year' => '2026-2027',
        'gender' => 'Female',
        'year_level' => '1st',
        'section' => 'A',
        'status' => 'active',
    ], $overrides);
}

test('admin can browse and search student athlete records', function () {
    $admin = activeAdmin();
    Student::factory()->create(['student_number' => '2026-10001', 'first_name' => 'Lina', 'last_name' => 'Garcia']);
    Student::factory()->create(['student_number' => '2026-10002', 'first_name' => 'Marco', 'last_name' => 'Dela Cruz']);

    $response = $this->actingAs($admin)->get(route('admin.students.index', ['search' => 'Lina']));

    $response->assertOk()->assertSee('Lina')->assertDontSee('Marco');
});

test('admin can create a student and the action is audited', function () {
    $admin = activeAdmin();

    $response = $this->actingAs($admin)->post(route('admin.students.store'), validStudentData([
        'first_name' => '  aNA  ',
        'middle_name' => '<b>sANTOS</b>',
        'last_name' => 'rEYES',
        'section' => 'a',
    ]));

    $response->assertRedirect(route('admin.students.index'));
    $this->assertDatabaseHas('students', ['student_number' => '2026-00001', 'first_name' => 'Ana', 'middle_name' => 'Santos', 'last_name' => 'Reyes', 'school_year' => '2026-2027', 'section' => 'A', 'status' => 'active']);
    $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'student.created', 'outcome' => 'success']);
});

test('student number must be unique', function () {
    $admin = activeAdmin();
    Student::factory()->create(['student_number' => '2026-00001']);

    $this->actingAs($admin)
        ->from(route('admin.students.create'))
        ->post(route('admin.students.store'), validStudentData())
        ->assertRedirect(route('admin.students.create'))
        ->assertSessionHasErrors('student_number');
});

test('admin can update, archive, and restore a student without a permanent deletion', function () {
    $admin = activeAdmin();
    $student = Student::factory()->create(['student_number' => '2026-00001', 'first_name' => 'Ana', 'last_name' => 'Reyes']);

    $this->actingAs($admin)
        ->put(route('admin.students.update', $student), validStudentData(['first_name' => 'Anabel', 'status' => 'inactive']))
        ->assertRedirect(route('admin.students.index'));
    $this->assertDatabaseHas('students', ['id' => $student->id, 'first_name' => 'Anabel', 'status' => 'inactive']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'student.updated']);

    $this->actingAs($admin)->delete(route('admin.students.destroy', $student))->assertRedirect(route('admin.students.index'));
    $this->assertSoftDeleted('students', ['id' => $student->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'student.archived']);

    $this->actingAs($admin)->post(route('admin.students.restore', $student->id))->assertRedirect(route('admin.students.index', ['status' => 'archived']));
    $this->assertDatabaseHas('students', ['id' => $student->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'student.restored']);
});

test('coordinators cannot manage student records', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);

    $this->actingAs($coordinator)->get(route('admin.students.index'))->assertForbidden();
    $this->actingAs($coordinator)->post(route('admin.students.store'), validStudentData())->assertForbidden();
});
