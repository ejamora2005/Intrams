<?php

use App\Models\CompetitionSchedule;
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

test('re-seeding default sports moves basketball 3x3 to the minor point system', function () {
    $edition = IntramuralEdition::query()->create([
        'name' => 'Legacy 3x3 Edition',
        'school_year' => '2026-2027',
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-05',
        'status' => 'draft',
    ]);
    $basketball3x3 = Sport::query()->create([
        'name' => 'Basketball 3x3',
        'code' => 'BASKET-3X3',
        'status' => 'active',
        'is_system' => true,
    ]);
    $configuration = EditionSport::query()->create([
        'edition_id' => $edition->id,
        'sport_id' => $basketball3x3->id,
        'participant_type' => 'team',
        'game_mechanic' => 'single_elimination',
        'scoring_rules' => [
            'point_system' => 'sports_major',
            'placements' => config('intramurals.point_systems.sports_major.placements'),
        ],
        'status' => 'preparation',
    ]);

    app(DefaultSportsSeeder::class)->run();

    expect($configuration->fresh()->scoring_rules['point_system'])->toBe('sports_minor');
});

test('proposal defaults seed scoring groups and cultural schedule items', function () {
    $admin = editionSportsAdmin();

    app(DefaultSportsSeeder::class)->run();

    $defaultEdition = config('intramurals.default_edition');
    $edition = IntramuralEdition::query()
        ->where('name', $defaultEdition['name'])
        ->where('school_year', $defaultEdition['school_year'])
        ->firstOrFail();
    $expectedCodes = collect(config('intramurals.default_competitions'))->pluck('code')->all();

    expect($edition->name)->toBe('SLSUBC INTRAMURALS 2026')
        ->and($edition->starts_on->format('Y-m-d'))->toBe('2026-10-19')
        ->and($edition->ends_on->format('Y-m-d'))->toBe('2026-10-23')
        ->and($edition->status)->toBe('active');

    expect(Sport::query()->whereIn('code', $expectedCodes)->where('is_system', true)->count())->toBe(count($expectedCodes))
        ->and(EditionSport::query()->where('edition_id', $edition->id)->whereHas('sport', fn ($query) => $query->whereIn('code', $expectedCodes))->count())->toBe(count($expectedCodes))
        ->and(CompetitionSchedule::query()->whereIn('edition_sport_id', $edition->editionSports()->select('id'))->whereNotNull('title')->count())->toBe(count(config('intramurals.default_schedules')));

    $basketball3x3 = EditionSport::query()->where('edition_id', $edition->id)->whereHas('sport', fn ($query) => $query->where('code', 'BASKET-3X3'))->firstOrFail();
    $basketball = EditionSport::query()->where('edition_id', $edition->id)->whereHas('sport', fn ($query) => $query->where('code', 'BASKET-5X5'))->firstOrFail();
    $chess = EditionSport::query()->where('edition_id', $edition->id)->whereHas('sport', fn ($query) => $query->where('code', 'CHESS'))->firstOrFail();
    $festivalDance = EditionSport::query()->where('edition_id', $edition->id)->whereHas('sport', fn ($query) => $query->where('code', 'CULT-FEST-DANCE'))->firstOrFail();
    $festivalProgram = EditionSport::query()->where('edition_id', $edition->id)->whereHas('sport', fn ($query) => $query->where('code', 'CULT-FESTIVAL-PROGRAM'))->firstOrFail();

    expect($basketball3x3->scoring_rules['point_system'])->toBe('sports_minor')
        ->and($basketball->scoring_rules['point_system'])->toBe('sports_major')
        ->and((float) $basketball->scoring_rules['placements'][0]['points'])->toBe(25.0)
        ->and($chess->scoring_rules['point_system'])->toBe('sports_minor')
        ->and($festivalDance->scoring_rules['point_system'])->toBe('cultural_festival_dance')
        ->and($festivalProgram->scoring_rules['non_scoring'])->toBeTrue();

    $run = CompetitionSchedule::query()->where('title', 'Ruperto Run 2026')->firstOrFail();
    $awarding = CompetitionSchedule::query()->where('title', 'Awarding')->firstOrFail();

    expect($run->starts_at->format('Y-m-d H:i'))->toBe('2026-10-19 06:00')
        ->and($run->venue)->toBe('TBA')
        ->and($awarding->starts_at->format('Y-m-d H:i'))->toBe('2026-10-23 13:00')
        ->and($awarding->venue)->toBe('TBA');

    $this->actingAs($admin)
        ->put(route('admin.competition.schedule-venue.update', $run), ['venue' => 'SSC Hall'])
        ->assertRedirect();

    $this->actingAs($admin)
        ->put(route('admin.competition.schedule-venue.update', $awarding), ['venue' => ''])
        ->assertRedirect();

    expect($run->fresh()->venue)->toBe('SSC Hall')
        ->and($awarding->fresh()->venue)->toBe('TBA');

    $this->actingAs($admin)
        ->put(route('admin.competition.schedules.update', $run), [
            'title' => 'Ruperto Run Qualifiers',
            'starts_at' => '2026-10-20T07:30',
            'ends_at' => '2026-10-20T09:00',
            'venue' => 'Open Court (Volleyball/Pickleball)',
            'status' => 'live',
        ])
        ->assertRedirect();

    expect($run->fresh()->title)->toBe('Ruperto Run Qualifiers')
        ->and($run->fresh()->starts_at->format('Y-m-d H:i'))->toBe('2026-10-20 07:30')
        ->and($run->fresh()->ends_at->format('Y-m-d H:i'))->toBe('2026-10-20 09:00')
        ->and($run->fresh()->venue)->toBe('Open Court (Volleyball/Pickleball)')
        ->and($run->fresh()->status)->toBe('live');

    $this->actingAs($admin)
        ->get(route('admin.competition.index'))
        ->assertOk()
        ->assertSee('Edit')
        ->assertSee('Ruperto Run Qualifiers')
        ->assertSee('Open Court (Volleyball/Pickleball)')
        ->assertSee('MPCC')
        ->assertSee('Field/Oval')
        ->assertSee('Live')
        ->assertSee('Awarding');

    $this->actingAs($admin)
        ->get(route('admin.sports-points.index'))
        ->assertOk()
        ->assertSee('Sports Major Events')
        ->assertSee('Save Sports Major Events')
        ->assertDontSee('Save sports_major points')
        ->assertSee('Sports Athletics Events')
        ->assertSee('Cultural Special Awards - 5 Points');
});
