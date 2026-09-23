<?php

use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\DefaultSportsSeeder;

function editionSportsAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'status' => 'active']);
}

test('creating an edition automatically configures every default sport', function () {
    $admin = editionSportsAdmin();
    Sport::query()->create(['name' => 'Default Team Sport', 'code' => 'DEFAULT-TEAM', 'status' => 'active', 'is_system' => true]);
    Sport::query()->create(['name' => 'Default Individual Sport', 'code' => 'DEFAULT-INDIVIDUAL', 'status' => 'active', 'is_system' => true]);

    $this->actingAs($admin)
        ->post(route('admin.editions.store'), [
            'name' => 'Automatic Sports Edition',
            'school_year' => '2026-2027',
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-05',
            'status' => 'draft',
        ])
        ->assertRedirect(route('admin.editions.index'));

    $edition = IntramuralEdition::query()->where('name', 'Automatic Sports Edition')->firstOrFail();
    expect(EditionSport::query()->where('edition_id', $edition->id)->count())->toBe(2);

    $this->assertDatabaseHas('edition_sports', ['edition_id' => $edition->id, 'sport_id' => Sport::query()->where('code', 'DEFAULT-TEAM')->value('id'), 'participant_type' => 'team']);
    $this->assertDatabaseHas('edition_sports', ['edition_id' => $edition->id, 'sport_id' => Sport::query()->where('code', 'DEFAULT-INDIVIDUAL')->value('id'), 'participant_type' => 'team']);
});

test('re-seeding default sports synchronizes them to existing editions and sports can be filtered by edition', function () {
    $admin = editionSportsAdmin();
    $firstEdition = IntramuralEdition::query()->create(['name' => 'First Edition', 'school_year' => '2026-2027', 'starts_on' => '2026-10-01', 'ends_on' => '2026-10-05', 'status' => 'draft']);
    $secondEdition = IntramuralEdition::query()->create(['name' => 'Second Edition', 'school_year' => '2027-2028', 'starts_on' => '2027-10-01', 'ends_on' => '2027-10-05', 'status' => 'draft']);
    $firstSport = Sport::query()->create(['name' => 'First Edition Only', 'code' => 'FIRST-ONLY', 'status' => 'active']);
    $secondSport = Sport::query()->create(['name' => 'Second Edition Only', 'code' => 'SECOND-ONLY', 'status' => 'active']);
    EditionSport::query()->create(['edition_id' => $firstEdition->id, 'sport_id' => $firstSport->id, 'participant_type' => 'team', 'game_mechanic' => 'single_elimination', 'status' => 'preparation']);
    EditionSport::query()->create(['edition_id' => $secondEdition->id, 'sport_id' => $secondSport->id, 'participant_type' => 'team', 'game_mechanic' => 'single_elimination', 'status' => 'preparation']);

    app(DefaultSportsSeeder::class)->run();

    $defaultSportCount = Sport::query()->where('is_system', true)->count();
    expect(EditionSport::query()->where('edition_id', $firstEdition->id)->whereHas('sport', fn ($query) => $query->where('is_system', true))->count())->toBe($defaultSportCount);
    expect(EditionSport::query()->where('edition_id', $secondEdition->id)->whereHas('sport', fn ($query) => $query->where('is_system', true))->count())->toBe($defaultSportCount);

    $this->actingAs($admin)
        ->get(route('admin.sports.index', ['edition_id' => $secondEdition->id]))
        ->assertOk()
        ->assertSee('Second Edition Only')
        ->assertDontSee('First Edition Only');
});
