<?php

$pointSystems = [
    'sports_major' => [
        'label' => 'Sports Major Events',
        'category' => 'sports',
        'description' => 'Major athletic events from the Intramurals 2026 proposal.',
        'codes' => [
            'BASKET-5X5', 'BASKET-5X5-MEN', 'BASKET-5X5-WOMEN',
            'VOLLEYBALL', 'VOLLEYBALL-MEN', 'VOLLEYBALL-WOMEN', 'MIXED-VOLLEYBALL', 'MODIFIED-VOLLEYBALL', 'MODIFIED-VOLLEYBALL-MEN', 'MODIFIED-VOLLEYBALL-WOMEN',
            'BASEBALL', 'SOFTBALL', 'RUSSIAN-SOFTBALL', 'ESPORT-ML', 'ESPORT-ML-MEN', 'ESPORT-ML-WOMEN',
        ],
        'placements' => [
            ['placement' => 1, 'label' => 'Champion', 'points' => 25.0, 'medal' => 'gold'],
            ['placement' => 2, 'label' => '1st Placer', 'points' => 20.0, 'medal' => 'silver'],
            ['placement' => 3, 'label' => '2nd Placer', 'points' => 15.0, 'medal' => 'bronze'],
        ],
    ],
    'sports_minor' => [
        'label' => 'Sports Minor Events',
        'category' => 'sports',
        'description' => 'Minor athletic events listed in the proposal.',
        'codes' => [
            'BASKET-3X3', 'BASKET-3X3-MEN', 'BASKET-3X3-WOMEN', 'CHESS', 'CHESS-MEN', 'CHESS-WOMEN',
            'BADMINTON', 'BADMINTON-TEAM', 'BADMINTON-DOUBLES', 'BADMINTON-SINGLES',
            'BADMINTON-TEAM-MEN', 'BADMINTON-TEAM-WOMEN', 'BADMINTON-DOUBLES-MEN', 'BADMINTON-DOUBLES-WOMEN',
            'BADMINTON-SINGLES-MEN', 'BADMINTON-SINGLES-WOMEN',
            'TABLE-TENNIS', 'TABLE-TENNIS-TEAM', 'TABLE-TENNIS-DOUBLES', 'TABLE-TENNIS-SINGLES',
            'TABLE-TENNIS-TEAM-MEN', 'TABLE-TENNIS-TEAM-WOMEN', 'TABLE-TENNIS-DOUBLES-MEN', 'TABLE-TENNIS-DOUBLES-WOMEN',
            'TABLE-TENNIS-SINGLES-MEN', 'TABLE-TENNIS-SINGLES-WOMEN',
            'BEACH-VOLLEYBALL', 'BEACH-VOLLEYBALL-MEN', 'BEACH-VOLLEYBALL-WOMEN',
        ],
        'placements' => [
            ['placement' => 1, 'label' => 'Champion', 'points' => 20.0, 'medal' => 'gold'],
            ['placement' => 2, 'label' => '1st Placer', 'points' => 15.0, 'medal' => 'silver'],
            ['placement' => 3, 'label' => '2nd Placer', 'points' => 10.0, 'medal' => 'bronze'],
        ],
    ],
    'sports_athletics' => [
        'label' => 'Sports Athletics Events',
        'category' => 'sports',
        'description' => 'Runs, throws, and jump events use the 5-3-2-1 proposal scale.',
        'codes' => [
            'RUNS', '100M-RUN', '200M-RUN', '400M-RUN',
            'RELAY-4X100', 'RELAY-4X400',
            'THROWS', 'SHOT-PUT', 'DISCUS-THROW', 'JAVELIN-THROW',
            'JUMP', 'LONG-JUMP', 'TRIPLE-JUMP', 'HIGH-JUMP',
            'RUNS-MEN', 'RUNS-WOMEN', '100M-RUN-MEN', '100M-RUN-WOMEN', '200M-RUN-MEN', '200M-RUN-WOMEN', '400M-RUN-MEN', '400M-RUN-WOMEN',
            'RELAY-4X100-MEN', 'RELAY-4X100-WOMEN', 'RELAY-4X400-MEN', 'RELAY-4X400-WOMEN',
            'THROWS-MEN', 'THROWS-WOMEN', 'SHOT-PUT-MEN', 'SHOT-PUT-WOMEN', 'DISCUS-THROW-MEN', 'DISCUS-THROW-WOMEN', 'JAVELIN-THROW-MEN', 'JAVELIN-THROW-WOMEN',
            'JUMP-MEN', 'JUMP-WOMEN', 'LONG-JUMP-MEN', 'LONG-JUMP-WOMEN', 'TRIPLE-JUMP-MEN', 'TRIPLE-JUMP-WOMEN', 'HIGH-JUMP-MEN', 'HIGH-JUMP-WOMEN',
        ],
        'placements' => [
            ['placement' => 1, 'label' => '1st Place', 'points' => 5.0, 'medal' => 'gold'],
            ['placement' => 2, 'label' => '2nd Place', 'points' => 3.0, 'medal' => 'silver'],
            ['placement' => 3, 'label' => '3rd Place', 'points' => 2.0, 'medal' => 'bronze'],
            ['placement' => 4, 'label' => '4th Place', 'points' => 1.0, 'medal' => 'none'],
        ],
    ],
    'cultural_festival_dance' => [
        'label' => 'Cultural Festival Dance',
        'category' => 'cultural',
        'description' => 'Festival Dance Competition point values.',
        'codes' => ['CULT-FEST-DANCE'],
        'placements' => [
            ['placement' => 1, 'label' => 'Champion', 'points' => 25.0, 'medal' => 'gold'],
            ['placement' => 2, 'label' => '1st Runner-up', 'points' => 20.0, 'medal' => 'silver'],
            ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 15.0, 'medal' => 'bronze'],
        ],
    ],
    'cultural_major_15_10_7' => [
        'label' => 'Cultural Major Events',
        'category' => 'cultural',
        'description' => 'Faction Pakulo, visual group work, short film, quiz, and treasure hunt events.',
        'codes' => ['CULT-FACTION-PAKULO', 'CULT-COLLAB-PAINT', 'CULT-SHORT-FILM', 'CULT-QUIZ', 'CULT-TREASURE'],
        'placements' => [
            ['placement' => 1, 'label' => 'Champion', 'points' => 15.0, 'medal' => 'gold'],
            ['placement' => 2, 'label' => '1st Runner-up', 'points' => 10.0, 'medal' => 'silver'],
            ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 7.0, 'medal' => 'bronze'],
        ],
    ],
    'cultural_group_dance' => [
        'label' => 'Cultural Group Dance',
        'category' => 'cultural',
        'description' => 'Sayawerte Random Group Dancing Challenge point values.',
        'codes' => ['CULT-SAYAWERTE-DANCE'],
        'placements' => [
            ['placement' => 1, 'label' => 'Champion', 'points' => 15.0, 'medal' => 'gold'],
            ['placement' => 2, 'label' => '1st Runner-up', 'points' => 10.0, 'medal' => 'silver'],
            ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 5.0, 'medal' => 'bronze'],
        ],
    ],
    'cultural_pageant' => [
        'label' => 'Cultural Pageant Titles',
        'category' => 'cultural',
        'description' => 'Intramural 2026 Student Festival Pageant title scoring.',
        'codes' => ['CULT-STUDENT-PAGEANT'],
        'placements' => [
            ['placement' => 1, 'label' => 'Major title', 'points' => 15.0, 'medal' => 'gold'],
            ['placement' => 2, 'label' => '1st Runner-up title', 'points' => 10.0, 'medal' => 'silver'],
            ['placement' => 3, 'label' => '2nd Runner-up title', 'points' => 5.0, 'medal' => 'bronze'],
        ],
    ],
    'cultural_standard_10_7_5' => [
        'label' => 'Cultural Standard Events',
        'category' => 'cultural',
        'description' => 'Digital, visual, singing, poetry, and Balitaw events.',
        'codes' => ['CULT-DIGITAL-POSTER', 'CULT-INFOGRAPHICS', 'CULT-DIGI-PHOTO', 'CULT-PENCIL-DRAWING', 'CULT-CHARCOAL', 'CULT-SING-SOLO', 'CULT-SING-DUET', 'CULT-BALAKWERTE', 'CULT-MASHUP-BALITAW'],
        'placements' => [
            ['placement' => 1, 'label' => 'Champion', 'points' => 10.0, 'medal' => 'gold'],
            ['placement' => 2, 'label' => '1st Runner-up', 'points' => 7.0, 'medal' => 'silver'],
            ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 5.0, 'medal' => 'bronze'],
        ],
    ],
    'cultural_special_2' => [
        'label' => 'Cultural Special Awards - 2 Points',
        'category' => 'cultural',
        'description' => 'Special awards that add 2 points.',
        'codes' => ['CULT-BONUS-FD-PART', 'CULT-BONUS-FP-PART', 'CULT-BONUS-FILM-SCORE', 'CULT-BONUS-FILM-ACT', 'CULT-BONUS-SING-FINAL'],
        'placements' => [
            ['placement' => 1, 'label' => 'Awardee', 'points' => 2.0, 'medal' => 'none'],
        ],
    ],
    'cultural_special_3' => [
        'label' => 'Cultural Special Awards - 3 Points',
        'category' => 'cultural',
        'description' => 'Special awards that add 3 points.',
        'codes' => ['CULT-BONUS-FD-COSTUME', 'CULT-BONUS-FILM-DIRECT', 'CULT-BONUS-MASHUP'],
        'placements' => [
            ['placement' => 1, 'label' => 'Awardee', 'points' => 3.0, 'medal' => 'none'],
        ],
    ],
    'cultural_special_5' => [
        'label' => 'Cultural Special Awards - 5 Points',
        'category' => 'cultural',
        'description' => 'Special awards that add 5 points.',
        'codes' => ['CULT-BONUS-FD-STREET'],
        'placements' => [
            ['placement' => 1, 'label' => 'Awardee', 'points' => 5.0, 'medal' => 'none'],
        ],
    ],
];

$rules = fn (string $key): array => [
    'point_system' => $key,
    'placements' => $pointSystems[$key]['placements'],
];

$cultural = function (string $name, string $code, string $description, string $pointSystem) use ($rules): array {
    return [
        'name' => 'Cultural: '.$name,
        'code' => $code,
        'description' => $description,
        'rules' => $description,
        'game_mechanic' => 'custom',
        'scoring_rules' => $rules($pointSystem),
    ];
};

$bonus = function (string $name, string $code, string $description, string $pointSystem) use ($rules): array {
    return [
        'name' => 'Cultural Bonus: '.$name,
        'code' => $code,
        'description' => 'Additional cultural points. '.$description,
        'rules' => $description,
        'game_mechanic' => 'custom',
        'scoring_rules' => $rules($pointSystem),
    ];
};

$defaultEdition = [
    'name' => env('INTRAMURALS_DEFAULT_EDITION_NAME', 'SLSUBC INTRAMURALS 2026'),
    'school_year' => env('INTRAMURALS_DEFAULT_SCHOOL_YEAR', '2026-2027'),
    'starts_on' => env('INTRAMURALS_DEFAULT_STARTS_ON', '2026-10-19'),
    'ends_on' => env('INTRAMURALS_DEFAULT_ENDS_ON', '2026-10-23'),
    'status' => 'active',
    'aliases' => [
        '2026 SLSU Bontoc-campus Intramurals',
        'Intramurals 2026 Student Festival',
    ],
];

$theme = [
    'title' => env('INTRAMURALS_THEME_TITLE', 'INTRAMURALS 2026: STUDENT FESTIVAL'),
    'tagline' => env('INTRAMURALS_THEME_TAGLINE', 'Compete. Create. Lead. Connect. Express.'),
];

$venues = [
    'TBA',
    'MPCC',
    'Field/Oval',
    'SSC Hall',
    'Open Court (Volleyball/Pickleball)',
];

$participationRules = [
    'max_events_per_student' => 2,
    'individual_dual_only_max_events' => 3,
    // Male Russian Softball entries are the partner side of a women's pair.
    'paired_partner_exemptions' => [
        'RUSSIAN-SOFTBALL' => ['Male'],
        'RUSSIAN-SOFTBALL-MEN' => ['Male'],
    ],
    // Relay runners represent their faction in addition to their individual run.
    // They do not use one of the student's participation-rule event slots.
    'relay_exempt_sport_codes' => ['RELAY-4X100', 'RELAY-4X400', 'RELAY-4X100-MEN', 'RELAY-4X100-WOMEN', 'RELAY-4X400-MEN', 'RELAY-4X400-WOMEN'],
    'relay_qualifying_run_sport_codes' => ['100M-RUN', '200M-RUN', '400M-RUN', '100M-RUN-MEN', '100M-RUN-WOMEN', '200M-RUN-MEN', '200M-RUN-WOMEN', '400M-RUN-MEN', '400M-RUN-WOMEN'],
    'allowed_combinations' => [
        ['label' => '1 minor + 1 major', 'major' => 1, 'minor' => 1, 'individual_or_dual' => 0],
        ['label' => '1 minor + 1 individual/dual', 'major' => 0, 'minor' => 1, 'individual_or_dual' => 1],
        ['label' => '1 major + 1 individual/dual', 'major' => 1, 'minor' => 0, 'individual_or_dual' => 1],
        ['label' => 'No major/minor: up to 3 individual/dual events', 'major' => 0, 'minor' => 0, 'individual_or_dual' => 3],
    ],
];

$medicalCertificate = [
    'exempt_sport_codes' => ['CHESS', 'CHESS-MEN', 'CHESS-WOMEN', 'ESPORT-ML', 'ESPORT-ML-MEN', 'ESPORT-ML-WOMEN'],
];

return [
    'default_edition' => $defaultEdition,

    'theme' => $theme,

    'venues' => $venues,

    'participation_rules' => $participationRules,

    'medical_certificate' => $medicalCertificate,

    'point_systems' => $pointSystems,

    'default_competitions' => [
        ['name' => 'Basketball 3x3 (Men)', 'code' => 'BASKET-3X3-MEN', 'participant_category' => 'male', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Basketball 3x3 (Women)', 'code' => 'BASKET-3X3-WOMEN', 'participant_category' => 'female', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Basketball 5x5 (Men)', 'code' => 'BASKET-5X5-MEN', 'participant_category' => 'male', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Basketball 5x5 (Women)', 'code' => 'BASKET-5X5-WOMEN', 'participant_category' => 'female', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Volleyball (Men)', 'code' => 'VOLLEYBALL-MEN', 'participant_category' => 'male', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Volleyball (Women)', 'code' => 'VOLLEYBALL-WOMEN', 'participant_category' => 'female', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Modified Volleyball (Men)', 'code' => 'MODIFIED-VOLLEYBALL-MEN', 'participant_category' => 'male', 'participant_type' => 'team', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Modified Volleyball (Women)', 'code' => 'MODIFIED-VOLLEYBALL-WOMEN', 'participant_category' => 'female', 'participant_type' => 'team', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Mixed Volleyball (Open)', 'code' => 'MIXED-VOLLEYBALL', 'participant_category' => 'mixed', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Baseball', 'code' => 'BASEBALL', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Softball', 'code' => 'SOFTBALL', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Russian Softball', 'code' => 'RUSSIAN-SOFTBALL', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'E-Sport (ML) (Men)', 'code' => 'ESPORT-ML-MEN', 'participant_category' => 'male', 'participant_type' => 'team', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'E-Sport (ML) (Women)', 'code' => 'ESPORT-ML-WOMEN', 'participant_category' => 'female', 'participant_type' => 'team', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Chess (Men)', 'code' => 'CHESS-MEN', 'participant_category' => 'male', 'participant_type' => 'team', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Chess (Women)', 'code' => 'CHESS-WOMEN', 'participant_category' => 'female', 'participant_type' => 'team', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Badminton (Team - Men)', 'code' => 'BADMINTON-TEAM-MEN', 'participant_category' => 'male', 'participant_type' => 'team', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Badminton (Team - Women)', 'code' => 'BADMINTON-TEAM-WOMEN', 'participant_category' => 'female', 'participant_type' => 'team', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Badminton (Doubles - Men)', 'code' => 'BADMINTON-DOUBLES-MEN', 'participant_category' => 'male', 'participant_type' => 'dual', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Badminton (Doubles - Women)', 'code' => 'BADMINTON-DOUBLES-WOMEN', 'participant_category' => 'female', 'participant_type' => 'dual', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Badminton (Singles - Men)', 'code' => 'BADMINTON-SINGLES-MEN', 'participant_category' => 'male', 'participant_type' => 'individual', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Badminton (Singles - Women)', 'code' => 'BADMINTON-SINGLES-WOMEN', 'participant_category' => 'female', 'participant_type' => 'individual', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Table Tennis (Team - Men)', 'code' => 'TABLE-TENNIS-TEAM-MEN', 'participant_category' => 'male', 'participant_type' => 'team', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Table Tennis (Team - Women)', 'code' => 'TABLE-TENNIS-TEAM-WOMEN', 'participant_category' => 'female', 'participant_type' => 'team', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Table Tennis (Doubles - Men)', 'code' => 'TABLE-TENNIS-DOUBLES-MEN', 'participant_category' => 'male', 'participant_type' => 'dual', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Table Tennis (Doubles - Women)', 'code' => 'TABLE-TENNIS-DOUBLES-WOMEN', 'participant_category' => 'female', 'participant_type' => 'dual', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Table Tennis (Singles - Men)', 'code' => 'TABLE-TENNIS-SINGLES-MEN', 'participant_category' => 'male', 'participant_type' => 'individual', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Table Tennis (Singles - Women)', 'code' => 'TABLE-TENNIS-SINGLES-WOMEN', 'participant_category' => 'female', 'participant_type' => 'individual', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Beach Volleyball (Men)', 'code' => 'BEACH-VOLLEYBALL-MEN', 'participant_category' => 'male', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Beach Volleyball (Women)', 'code' => 'BEACH-VOLLEYBALL-WOMEN', 'participant_category' => 'female', 'scoring_rules' => $rules('sports_minor')],
        ...collect([
            ['Runs', 'RUNS', 'individual'], ['100m Run', '100M-RUN', 'individual'], ['200m Run', '200M-RUN', 'individual'], ['400m Run', '400M-RUN', 'individual'],
            ['4x100m Relay', 'RELAY-4X100', 'team'], ['4x400m Relay', 'RELAY-4X400', 'team'], ['Throws', 'THROWS', 'individual'], ['Shot Put', 'SHOT-PUT', 'individual'],
            ['Discus Throw', 'DISCUS-THROW', 'individual'], ['Javelin Throw', 'JAVELIN-THROW', 'individual'], ['Jump', 'JUMP', 'individual'], ['Long Jump', 'LONG-JUMP', 'individual'],
            ['Triple Jump', 'TRIPLE-JUMP', 'individual'], ['High Jump', 'HIGH-JUMP', 'individual'],
        ])->flatMap(fn (array $sport): array => collect(['Men' => 'male', 'Women' => 'female'])->map(fn (string $category, string $label): array => [
            'name' => $sport[0].' ('.$label.')', 'code' => $sport[1].'-'.strtoupper($label), 'participant_category' => $category, 'participant_type' => $sport[2], 'game_mechanic' => 'custom', 'scoring_rules' => $rules('sports_athletics'),
        ])->values()->all())->values()->all(),

        $cultural('Festival Dance Competition', 'CULT-FEST-DANCE', 'Students: 40 minimum; props or propsmen excluded. Employees: none.', 'cultural_festival_dance'),
        $cultural('Faction Pakulo', 'CULT-FACTION-PAKULO', 'Students: not specified. Employees: encouraged.', 'cultural_major_15_10_7'),
        $cultural('Sayawerte Random Group Dancing Challenge', 'CULT-SAYAWERTE-DANCE', 'Students: 6 minimum, 8 maximum. Employees: 5 minimum, 8 maximum.', 'cultural_group_dance'),
        $cultural('Intramural 2026 Student Festival Pageant', 'CULT-STUDENT-PAGEANT', 'Maximum of 8 candidates: 3 males, 3 females, 1 LGBTQIA+ male, and 1 LGBTQIA+ female.', 'cultural_pageant'),
        $cultural('Digital Poster Contest', 'CULT-DIGITAL-POSTER', 'Maximum 2 students: 1 male and 1 female.', 'cultural_standard_10_7_5'),
        $cultural('Infographics Making Contest', 'CULT-INFOGRAPHICS', 'Maximum 2 students: 1 male and 1 female.', 'cultural_standard_10_7_5'),
        $cultural('Digital Photography', 'CULT-DIGI-PHOTO', 'Maximum 2 students: 1 male and 1 female.', 'cultural_standard_10_7_5'),
        $cultural('Collaborative Painting', 'CULT-COLLAB-PAINT', 'No participant limit specified.', 'cultural_major_15_10_7'),
        $cultural('Pencil Drawing', 'CULT-PENCIL-DRAWING', 'Maximum 2 students: 1 male and 1 female.', 'cultural_standard_10_7_5'),
        $cultural('Charcoal Rendering', 'CULT-CHARCOAL', 'Maximum 2 students: 1 male and 1 female.', 'cultural_standard_10_7_5'),
        $cultural('Short Film - Last na ni!', 'CULT-SHORT-FILM', '4-8 members with 1:1 male-to-female ratio.', 'cultural_major_15_10_7'),
        $cultural('Singswerte Solo Random Song Challenge', 'CULT-SING-SOLO', 'Students: up to 3 contestants. Employees: up to 2 contestants.', 'cultural_standard_10_7_5'),
        $cultural('Singswerte Duet Random Song Challenge', 'CULT-SING-DUET', 'Students: up to 2 pairs. Employees: 1 pair.', 'cultural_standard_10_7_5'),
        $cultural('Balakwerte Spoken Word Poetry/Balak Challenge', 'CULT-BALAKWERTE', '1 contestant or team of up to 3 members.', 'cultural_standard_10_7_5'),
        $cultural('Mash-Up Gen Z Balitaw', 'CULT-MASHUP-BALITAW', '2 principal performers; supporting performers may be included.', 'cultural_standard_10_7_5'),
        $cultural('Isiphenyo Creative Quiz Challenge', 'CULT-QUIZ', 'Students: 4 students. Employees: 2 coaches.', 'cultural_major_15_10_7'),
        $cultural('Treasurehenyo Creative Survival Treasure Hunt', 'CULT-TREASURE', '10 students: 5 males and 5 females. Employees: 5.', 'cultural_major_15_10_7'),
        [
            'name' => 'Cultural: Festival Program',
            'code' => 'CULT-FESTIVAL-PROGRAM',
            'description' => 'Non-scoring festival schedule item.',
            'rules' => 'Used for parade, opening, fireworks, and awarding schedule entries.',
            'game_mechanic' => 'custom',
            'scoring_rules' => ['non_scoring' => true, 'placements' => []],
        ],

        $bonus('Festival Dance - Most Number of Participants', 'CULT-BONUS-FD-PART', 'Special award: Most Number of Participants.', 'cultural_special_2'),
        $bonus('Festival Dance - Best in Costume', 'CULT-BONUS-FD-COSTUME', 'Special award: Best in Costume.', 'cultural_special_3'),
        $bonus('Festival Dance - Best Streetdance/Parade Performance', 'CULT-BONUS-FD-STREET', 'Special award: Best Streetdance/Parade Performance.', 'cultural_special_5'),
        $bonus('Faction Pakulo - Most Number of Participants', 'CULT-BONUS-FP-PART', 'Special award: Most Number of Participants.', 'cultural_special_2'),
        $bonus('Short Film - Best Directing', 'CULT-BONUS-FILM-DIRECT', 'Special award: Best Directing.', 'cultural_special_3'),
        $bonus('Short Film - Best Musical Score', 'CULT-BONUS-FILM-SCORE', 'Special award: Best Musical Score.', 'cultural_special_2'),
        $bonus('Short Film - Best Portrayal in a Leading Role', 'CULT-BONUS-FILM-ACT', 'Special award: Best Portrayal in a Leading Role.', 'cultural_special_2'),
        $bonus('Singswerte Solo - Finalist', 'CULT-BONUS-SING-FINAL', 'Special award: Finalist.', 'cultural_special_2'),
        $bonus('Mash-Up Gen Z Balitaw - Best Musical Mash-Up', 'CULT-BONUS-MASHUP', 'Special award: Best Musical Mash-Up.', 'cultural_special_3'),
    ],

    'default_schedules' => [
        ['code' => 'RUNS-MEN', 'title' => 'Ruperto Run 2026', 'is_activity' => true, 'day' => 1, 'starts_at' => '06:00', 'ends_at' => '08:00', 'venue' => 'Field/Oval'],
        ['code' => 'CULT-FESTIVAL-PROGRAM', 'title' => 'Team Officials Meeting (Sports)', 'is_activity' => true, 'day' => 1, 'starts_at' => '07:00', 'ends_at' => '08:00'],
        ['code' => 'CULT-DIGITAL-POSTER', 'title' => 'Visual Art Exhibition', 'day' => 1, 'starts_at' => '08:00', 'ends_at' => '12:00'],
        ['code' => 'CULT-FESTIVAL-PROGRAM', 'title' => 'Parade', 'is_activity' => true, 'day' => 1, 'starts_at' => '13:00', 'ends_at' => '14:00'],
        ['code' => 'CULT-FESTIVAL-PROGRAM', 'title' => 'Opening Festival', 'is_activity' => true, 'day' => 1, 'starts_at' => '14:00', 'ends_at' => '15:00'],
        ['code' => 'CULT-FEST-DANCE', 'title' => 'Festival Dance Showdown', 'day' => 1, 'starts_at' => '15:00', 'ends_at' => '17:00'],
        ['code' => 'CULT-STUDENT-PAGEANT', 'title' => 'Agility and Sportswear Contest', 'day' => 1, 'starts_at' => '18:00', 'ends_at' => '20:00'],
        ['code' => 'CULT-FESTIVAL-PROGRAM', 'title' => 'Fireworks Display', 'is_activity' => true, 'day' => 1, 'starts_at' => '20:00', 'ends_at' => '21:00'],

        ['code' => 'RUNS-MEN', 'title' => 'Athletics Runs Finals (M&W)', 'day' => 2, 'starts_at' => '08:00', 'ends_at' => '10:00', 'venue' => 'Field/Oval'],
        ['code' => 'VOLLEYBALL-MEN', 'title' => 'Volleyball Elimination (M&W)', 'day' => 2, 'starts_at' => '08:00', 'ends_at' => '10:00', 'venue' => 'Open Court (Volleyball/Pickleball)'],
        ['code' => 'TABLE-TENNIS-TEAM-MEN', 'title' => 'Table Tennis Team (M&W)', 'day' => 2, 'starts_at' => '09:00', 'ends_at' => '11:00'],
        ['code' => 'TABLE-TENNIS-SINGLES-MEN', 'title' => 'Table Tennis Individual (M&W)', 'day' => 2, 'starts_at' => '10:00', 'ends_at' => '12:00'],
        ['code' => 'CHESS-MEN', 'title' => 'Chess Elimination (M&W)', 'day' => 2, 'starts_at' => '11:00', 'ends_at' => '13:00'],
        ['code' => 'CULT-DIGITAL-POSTER', 'title' => 'Visual Art Contest', 'day' => 2, 'starts_at' => '08:00', 'ends_at' => '12:00'],
        ['code' => 'BASKET-5X5-MEN', 'title' => 'Basketball Elimination (M&W)', 'day' => 2, 'starts_at' => '13:00', 'ends_at' => '15:00', 'venue' => 'MPCC'],
        ['code' => 'ESPORT-ML-MEN', 'title' => 'ML Elimination (M&W)', 'day' => 2, 'starts_at' => '15:00', 'ends_at' => '17:00'],
        ['code' => 'CULT-SING-SOLO', 'title' => 'Singswerte Solo Random Song Challenge', 'day' => 2, 'starts_at' => '13:00', 'ends_at' => '14:30'],
        ['code' => 'CULT-SING-DUET', 'title' => 'Singswerte Duet Random Song Challenge', 'day' => 2, 'starts_at' => '14:30', 'ends_at' => '16:00'],
        ['code' => 'CULT-SAYAWERTE-DANCE', 'title' => 'Sayawerte Random Group Dancing Challenge', 'day' => 2, 'starts_at' => '16:00', 'ends_at' => '17:30'],
        ['code' => 'CULT-BALAKWERTE', 'title' => 'Balakwerte Random Spoken Word Poetry/Balak', 'day' => 2, 'starts_at' => '17:30', 'ends_at' => '19:00'],
        ['code' => 'CULT-STUDENT-PAGEANT', 'title' => 'Interview and Elegance Round', 'day' => 2, 'starts_at' => '19:00', 'ends_at' => '21:00'],

        ['code' => 'BADMINTON-SINGLES-MEN', 'title' => 'Badminton Individual Finals (M&W)', 'day' => 3, 'starts_at' => '08:00', 'ends_at' => '10:00', 'venue' => 'Open Court (Volleyball/Pickleball)'],
        ['code' => 'BADMINTON-DOUBLES-MEN', 'title' => 'Badminton Doubles Finals (M&W)', 'day' => 3, 'starts_at' => '09:00', 'ends_at' => '11:00', 'venue' => 'Open Court (Volleyball/Pickleball)'],
        ['code' => 'BADMINTON-TEAM-MEN', 'title' => 'Badminton Team Finals (M&W)', 'day' => 3, 'starts_at' => '10:00', 'ends_at' => '12:00', 'venue' => 'Open Court (Volleyball/Pickleball)'],
        ['code' => 'BEACH-VOLLEYBALL-MEN', 'title' => 'Beach Volleyball Finals (M&W)', 'day' => 3, 'starts_at' => '11:00', 'ends_at' => '13:00', 'venue' => 'Open Court (Volleyball/Pickleball)'],
        ['code' => 'CULT-QUIZ', 'title' => 'Isiphenyo Creative Quiz Challenge', 'day' => 3, 'starts_at' => '08:00', 'ends_at' => '12:00'],
        ['code' => 'BASKET-5X5-MEN', 'title' => 'Basketball Finals (M&W)', 'day' => 3, 'starts_at' => '13:00', 'ends_at' => '15:00', 'venue' => 'MPCC'],
        ['code' => 'ESPORT-ML-MEN', 'title' => 'ML Finals (M&W)', 'day' => 3, 'starts_at' => '13:00', 'ends_at' => '15:00'],
        ['code' => 'CULT-DIGITAL-POSTER', 'title' => 'Visual Arts Showcase', 'day' => 3, 'starts_at' => '13:00', 'ends_at' => '15:00'],
        ['code' => 'CULT-SHORT-FILM', 'title' => 'Short Film Festival', 'day' => 3, 'starts_at' => '15:00', 'ends_at' => '18:00'],
        ['code' => 'CULT-FESTIVAL-PROGRAM', 'title' => 'Alumni Games', 'is_activity' => true, 'day' => 3, 'starts_at' => '15:00', 'ends_at' => '18:00'],

        ['code' => 'BASEBALL', 'title' => 'Baseball Elimination', 'day' => 4, 'starts_at' => '08:00', 'ends_at' => '10:00'],
        ['code' => 'RUSSIAN-SOFTBALL', 'title' => 'Russian Softball Elimination', 'day' => 4, 'starts_at' => '08:00', 'ends_at' => '10:00'],
        ['code' => 'BASKET-3X3-MEN', 'title' => 'Basketball 3x3 Finals (M&W)', 'day' => 4, 'starts_at' => '08:00', 'ends_at' => '10:00', 'venue' => 'MPCC'],
        ['code' => 'THROWS-MEN', 'title' => 'Throws Finals (M&W)', 'day' => 4, 'starts_at' => '09:00', 'ends_at' => '11:00', 'venue' => 'Field/Oval'],
        ['code' => 'JUMP-MEN', 'title' => 'Jumps Final (M&W)', 'day' => 4, 'starts_at' => '10:00', 'ends_at' => '12:00', 'venue' => 'Field/Oval'],
        ['code' => 'RELAY-4X100-MEN', 'title' => 'Relays Finals', 'day' => 4, 'starts_at' => '11:00', 'ends_at' => '13:00', 'venue' => 'Field/Oval'],
        ['code' => 'CULT-DIGITAL-POSTER', 'title' => 'Visual Arts Judging', 'day' => 4, 'starts_at' => '08:00', 'ends_at' => '12:00'],
        ['code' => 'VOLLEYBALL-MEN', 'title' => 'Volleyball Finals (M&W)', 'day' => 4, 'starts_at' => '13:00', 'ends_at' => '15:00', 'venue' => 'Open Court (Volleyball/Pickleball)'],
        ['code' => 'BASEBALL', 'title' => 'Baseball Finals', 'day' => 4, 'starts_at' => '14:00', 'ends_at' => '16:00'],
        ['code' => 'RUSSIAN-SOFTBALL', 'title' => 'Russian Softball Finals', 'day' => 4, 'starts_at' => '15:00', 'ends_at' => '17:00'],
        ['code' => 'CULT-FESTIVAL-PROGRAM', 'title' => 'Alumni Games', 'is_activity' => true, 'day' => 4, 'starts_at' => '16:00', 'ends_at' => '18:00'],

        ['code' => 'MODIFIED-VOLLEYBALL-MEN', 'title' => 'Modified Volleyball Elimination (M&W)', 'day' => 5, 'starts_at' => '08:00', 'ends_at' => '10:00', 'venue' => 'Open Court (Volleyball/Pickleball)'],
        ['code' => 'MODIFIED-VOLLEYBALL-MEN', 'title' => 'Modified Volleyball Finals (M&W)', 'day' => 5, 'starts_at' => '13:00', 'ends_at' => '15:00', 'venue' => 'Open Court (Volleyball/Pickleball)'],
        ['code' => 'CULT-FESTIVAL-PROGRAM', 'title' => 'Alumni Games', 'is_activity' => true, 'day' => 5, 'starts_at' => '15:00', 'ends_at' => '17:00'],
        ['code' => 'CULT-FESTIVAL-PROGRAM', 'title' => 'Awarding', 'is_activity' => true, 'day' => 5, 'starts_at' => '13:00', 'ends_at' => '17:00'],
    ],
];
