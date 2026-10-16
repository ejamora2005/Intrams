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
        'name' => 'Basketball 3x3 (Men)',
        'code' => 'BASKET-3X3-MEN',
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

test('sports catalogue is organized into visible competition groups', function () {
    $admin = editionSportsAdmin();

    app(DefaultSportsSeeder::class)->run();

    $edition = IntramuralEdition::query()
        ->where('name', config('intramurals.default_edition.name'))
        ->where('school_year', config('intramurals.default_edition.school_year'))
        ->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.sports.index', ['edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('Team Sports')
        ->assertSee('Individual & Pair Sports')
        ->assertSee('Track & Field')
        ->assertSee('Mixed Volleyball (Open)')
        ->assertSee('Badminton');
});

test('complete sport catalogue includes gendered volleyball and individual athletics events', function () {
    app(DefaultSportsSeeder::class)->run();

    $expected = [
        'VOLLEYBALL-MEN' => ['name' => 'Volleyball (Men)', 'participant_category' => 'male'],
        'VOLLEYBALL-WOMEN' => ['name' => 'Volleyball (Women)', 'participant_category' => 'female'],
        'MIXED-VOLLEYBALL' => ['name' => 'Mixed Volleyball (Open)', 'participant_category' => 'mixed'],
        'BEACH-VOLLEYBALL-MEN' => ['name' => 'Beach Volleyball (Men)', 'participant_category' => 'male'],
        'BEACH-VOLLEYBALL-WOMEN' => ['name' => 'Beach Volleyball (Women)', 'participant_category' => 'female'],
        'CHESS-MEN' => ['name' => 'Chess (Men)', 'participant_category' => 'male'],
        'CHESS-WOMEN' => ['name' => 'Chess (Women)', 'participant_category' => 'female'],
        'BADMINTON-TEAM-MEN' => ['name' => 'Badminton (Team - Men)', 'participant_category' => 'male'],
        'BADMINTON-TEAM-WOMEN' => ['name' => 'Badminton (Team - Women)', 'participant_category' => 'female'],
        'BADMINTON-DOUBLES-MEN' => ['name' => 'Badminton (Doubles - Men)', 'participant_category' => 'male'],
        'BADMINTON-DOUBLES-WOMEN' => ['name' => 'Badminton (Doubles - Women)', 'participant_category' => 'female'],
        'BADMINTON-SINGLES-MEN' => ['name' => 'Badminton (Singles - Men)', 'participant_category' => 'male'],
        'BADMINTON-SINGLES-WOMEN' => ['name' => 'Badminton (Singles - Women)', 'participant_category' => 'female'],
        'TABLE-TENNIS-TEAM-MEN' => ['name' => 'Table Tennis (Team - Men)', 'participant_category' => 'male'],
        'TABLE-TENNIS-TEAM-WOMEN' => ['name' => 'Table Tennis (Team - Women)', 'participant_category' => 'female'],
        'TABLE-TENNIS-DOUBLES-MEN' => ['name' => 'Table Tennis (Doubles - Men)', 'participant_category' => 'male'],
        'TABLE-TENNIS-DOUBLES-WOMEN' => ['name' => 'Table Tennis (Doubles - Women)', 'participant_category' => 'female'],
        'TABLE-TENNIS-SINGLES-MEN' => ['name' => 'Table Tennis (Singles - Men)', 'participant_category' => 'male'],
        'TABLE-TENNIS-SINGLES-WOMEN' => ['name' => 'Table Tennis (Singles - Women)', 'participant_category' => 'female'],
        'RUNS-MEN' => ['name' => 'Runs (Men)', 'participant_category' => 'male'],
        'RUNS-WOMEN' => ['name' => 'Runs (Women)', 'participant_category' => 'female'],
        '100M-RUN-MEN' => ['name' => '100m Run (Men)', 'participant_category' => 'male'],
        '100M-RUN-WOMEN' => ['name' => '100m Run (Women)', 'participant_category' => 'female'],
        '200M-RUN-MEN' => ['name' => '200m Run (Men)', 'participant_category' => 'male'],
        '200M-RUN-WOMEN' => ['name' => '200m Run (Women)', 'participant_category' => 'female'],
        '400M-RUN-MEN' => ['name' => '400m Run (Men)', 'participant_category' => 'male'],
        '400M-RUN-WOMEN' => ['name' => '400m Run (Women)', 'participant_category' => 'female'],
        'RELAY-4X100-MEN' => ['name' => '4x100m Relay (Men)', 'participant_category' => 'male'],
        'RELAY-4X100-WOMEN' => ['name' => '4x100m Relay (Women)', 'participant_category' => 'female'],
        'RELAY-4X400-MEN' => ['name' => '4x400m Relay (Men)', 'participant_category' => 'male'],
        'RELAY-4X400-WOMEN' => ['name' => '4x400m Relay (Women)', 'participant_category' => 'female'],
        'THROWS-MEN' => ['name' => 'Throws (Men)', 'participant_category' => 'male'],
        'THROWS-WOMEN' => ['name' => 'Throws (Women)', 'participant_category' => 'female'],
        'SHOT-PUT-MEN' => ['name' => 'Shot Put (Men)', 'participant_category' => 'male'],
        'SHOT-PUT-WOMEN' => ['name' => 'Shot Put (Women)', 'participant_category' => 'female'],
        'DISCUS-THROW-MEN' => ['name' => 'Discus Throw (Men)', 'participant_category' => 'male'],
        'DISCUS-THROW-WOMEN' => ['name' => 'Discus Throw (Women)', 'participant_category' => 'female'],
        'JAVELIN-THROW-MEN' => ['name' => 'Javelin Throw (Men)', 'participant_category' => 'male'],
        'JAVELIN-THROW-WOMEN' => ['name' => 'Javelin Throw (Women)', 'participant_category' => 'female'],
        'JUMP-MEN' => ['name' => 'Jump (Men)', 'participant_category' => 'male'],
        'JUMP-WOMEN' => ['name' => 'Jump (Women)', 'participant_category' => 'female'],
        'LONG-JUMP-MEN' => ['name' => 'Long Jump (Men)', 'participant_category' => 'male'],
        'LONG-JUMP-WOMEN' => ['name' => 'Long Jump (Women)', 'participant_category' => 'female'],
        'TRIPLE-JUMP-MEN' => ['name' => 'Triple Jump (Men)', 'participant_category' => 'male'],
        'TRIPLE-JUMP-WOMEN' => ['name' => 'Triple Jump (Women)', 'participant_category' => 'female'],
        'HIGH-JUMP-MEN' => ['name' => 'High Jump (Men)', 'participant_category' => 'male'],
        'HIGH-JUMP-WOMEN' => ['name' => 'High Jump (Women)', 'participant_category' => 'female'],
    ];

    foreach ($expected as $code => $attributes) {
        $sport = Sport::query()->where('code', $code)->firstOrFail();

        expect($sport->name)->toBe($attributes['name'])
            ->and($sport->participant_category)->toBe($attributes['participant_category']);
    }

    expect(Sport::catalogueGroupKeyFor('VOLLEYBALL-MEN'))->toBe('team')
        ->and(Sport::catalogueGroupKeyFor('BADMINTON-DOUBLES-MEN'))->toBe('individual')
        ->and(Sport::catalogueGroupKeyFor('RELAY-4X100-MEN'))->toBe('track-field')
        ->and(Sport::catalogueGroupKeyFor('TRIPLE-JUMP-WOMEN'))->toBe('track-field');
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

    $basketball3x3 = EditionSport::query()->where('edition_id', $edition->id)->whereHas('sport', fn ($query) => $query->where('code', 'BASKET-3X3-MEN'))->firstOrFail();
    $basketball = EditionSport::query()->where('edition_id', $edition->id)->whereHas('sport', fn ($query) => $query->where('code', 'BASKET-5X5-MEN'))->firstOrFail();
    $chess = EditionSport::query()->where('edition_id', $edition->id)->whereHas('sport', fn ($query) => $query->where('code', 'CHESS-MEN'))->firstOrFail();
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
    $implementationTitles = [
        'Team Officials Meeting (Sports)',
        'Athletics Runs Finals (M&W)',
        'Volleyball Elimination (M&W)',
        'Badminton Individual Finals (M&W)',
        'Basketball 3x3 Finals (M&W)',
        'Russian Softball Finals',
        'Modified Volleyball Finals (M&W)',
        'Alumni Games',
    ];

    expect($run->starts_at->format('Y-m-d H:i'))->toBe('2026-10-19 06:00')
        ->and($run->is_activity)->toBeTrue()
        ->and($run->venue)->toBe('Field/Oval')
        ->and($awarding->starts_at->format('Y-m-d H:i'))->toBe('2026-10-23 13:00')
        ->and($awarding->venue)->toBe('TBA')
        ->and(CompetitionSchedule::query()->whereIn('title', $implementationTitles)->distinct('title')->count('title'))->toBe(count($implementationTitles))
        ->and(CompetitionSchedule::query()->where('title', 'Athletics Runs Finals (M&W)')->value('venue'))->toBe('Field/Oval')
        ->and(CompetitionSchedule::query()->where('title', 'Basketball 3x3 Finals (M&W)')->value('venue'))->toBe('MPCC');

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
        ->assertSee('Live');

    $this->actingAs($admin)
        ->get(route('admin.competition.index', ['page' => 3]))
        ->assertOk()
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
