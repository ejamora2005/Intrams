<?php

use App\Models\IntramuralEdition;
use App\Models\User;

function editionModuleAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'status' => 'active']);
}

function validEditionData(array $overrides = []): array
{
    return array_merge([
        'name' => '2026 Intramurals',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-31',
        'status' => 'draft',
    ], $overrides);
}

test('admin can create and search intramurals editions and the action is audited', function () {
    $admin = editionModuleAdmin();

    $response = $this->actingAs($admin)->post(route('admin.editions.store'), validEditionData());
    $response->assertRedirect(route('admin.editions.index'));
    $this->assertDatabaseHas('intramural_editions', ['name' => '2026 Intramurals', 'school_year' => '2026-2027']);
    $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'edition.created']);

    IntramuralEdition::query()->create(validEditionData(['name' => '2025 Intramurals', 'school_year' => '2025-2026']));
    $this->actingAs($admin)->get(route('admin.editions.index', ['search' => '2026 Intramurals']))
        ->assertOk()->assertSee('2026 Intramurals')->assertDontSee('2025 Intramurals');
});

test('edition validation checks unique name and school year and ordered dates', function () {
    $admin = editionModuleAdmin();
    IntramuralEdition::query()->create(validEditionData());

    $this->actingAs($admin)->from(route('admin.editions.create'))
        ->post(route('admin.editions.store'), validEditionData(['ends_on' => '2026-09-30']))
        ->assertRedirect(route('admin.editions.create'))
        ->assertSessionHasErrors(['name', 'ends_on']);
});

test('only one edition can be active at a time', function () {
    $admin = editionModuleAdmin();
    IntramuralEdition::query()->create(validEditionData(['status' => 'active']));

    $this->actingAs($admin)->post(route('admin.editions.store'), validEditionData([
        'name' => '2027 Intramurals',
        'school_year' => '2027-2028',
        'starts_on' => '2027-10-01',
        'ends_on' => '2027-10-31',
        'status' => 'active',
    ]))->assertStatus(422);
});

test('admin can update and archive an edition without deleting related history', function () {
    $admin = editionModuleAdmin();
    $edition = IntramuralEdition::query()->create(validEditionData());

    $this->actingAs($admin)->put(route('admin.editions.update', $edition), validEditionData(['name' => '2026 Campus Intramurals', 'status' => 'active']))
        ->assertRedirect(route('admin.editions.index'));
    $this->assertDatabaseHas('intramural_editions', ['id' => $edition->id, 'name' => '2026 Campus Intramurals', 'status' => 'active']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'edition.updated']);

    $this->actingAs($admin)->delete(route('admin.editions.destroy', $edition))->assertRedirect(route('admin.editions.index'));
    $this->assertDatabaseHas('intramural_editions', ['id' => $edition->id, 'status' => 'archived']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'edition.archived']);
});

test('coordinators cannot manage intramurals editions', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);

    $this->actingAs($coordinator)->get(route('admin.editions.index'))->assertForbidden();
    $this->actingAs($coordinator)->post(route('admin.editions.store'), validEditionData())->assertForbidden();
});
