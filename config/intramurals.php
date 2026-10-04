<?php

$pointSystems = [
    'sports_major' => [
        'label' => 'Sports Major Events',
        'category' => 'sports',
        'description' => 'Major athletic events from the Intramurals 2026 proposal.',
        'codes' => ['BASKET-3X3', 'BASKET-5X5', 'VOLLEYBALL', 'BASEBALL', 'SOFTBALL', 'RUSSIAN-SOFTBALL', 'ESPORT-ML'],
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
        'codes' => ['CHESS', 'BADMINTON', 'TABLE-TENNIS', 'BEACH-VOLLEYBALL'],
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
        'codes' => ['RUNS', 'THROWS', 'SHOT-PUT', 'DISCUS-THROW', 'JAVELIN-THROW', 'JUMP'],
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

$venues = [
    'TBA',
    'MPCC',
    'Field/Oval',
    'SSC Hall',
    'Open Court (Volleyball/Pickleball)',
];

$participationRules = [
    'max_events_per_student' => 2,
    'allowed_combinations' => [
        ['label' => '1 minor + 1 major', 'major' => 1, 'minor' => 1, 'individual_or_dual' => 0],
        ['label' => '1 minor + 1 individual/dual', 'major' => 0, 'minor' => 1, 'individual_or_dual' => 1],
        ['label' => '1 major + 1 individual/dual', 'major' => 1, 'minor' => 0, 'individual_or_dual' => 1],
        ['label' => 'No major/minor: up to 2 individual/dual events', 'major' => 0, 'minor' => 0, 'individual_or_dual' => 2],
    ],
];

$medicalCertificate = [
    'exempt_sport_codes' => ['CHESS', 'ESPORT-ML'],
];

return [
    'default_edition' => $defaultEdition,

    'venues' => $venues,

    'participation_rules' => $participationRules,

    'medical_certificate' => $medicalCertificate,

    'point_systems' => $pointSystems,

    'default_competitions' => [
        ['name' => 'Basketball 3x3', 'code' => 'BASKET-3X3', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Basketball 5x5', 'code' => 'BASKET-5X5', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Volleyball', 'code' => 'VOLLEYBALL', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Baseball', 'code' => 'BASEBALL', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Softball', 'code' => 'SOFTBALL', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Russian Softball', 'code' => 'RUSSIAN-SOFTBALL', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'E-Sport (ML)', 'code' => 'ESPORT-ML', 'scoring_rules' => $rules('sports_major')],
        ['name' => 'Chess', 'code' => 'CHESS', 'participant_type' => 'individual', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Badminton (SDS)', 'code' => 'BADMINTON', 'participant_type' => 'dual', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Table Tennis', 'code' => 'TABLE-TENNIS', 'participant_type' => 'individual', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Beach Volleyball', 'code' => 'BEACH-VOLLEYBALL', 'scoring_rules' => $rules('sports_minor')],
        ['name' => 'Runs', 'code' => 'RUNS', 'participant_type' => 'individual', 'game_mechanic' => 'custom', 'scoring_rules' => $rules('sports_athletics')],
        ['name' => 'Throws', 'code' => 'THROWS', 'participant_type' => 'individual', 'game_mechanic' => 'custom', 'scoring_rules' => $rules('sports_athletics')],
        ['name' => 'Shot Put', 'code' => 'SHOT-PUT', 'participant_type' => 'individual', 'game_mechanic' => 'custom', 'scoring_rules' => $rules('sports_athletics')],
        ['name' => 'Discus Throw', 'code' => 'DISCUS-THROW', 'participant_type' => 'individual', 'game_mechanic' => 'custom', 'scoring_rules' => $rules('sports_athletics')],
        ['name' => 'Javelin Throw', 'code' => 'JAVELIN-THROW', 'participant_type' => 'individual', 'game_mechanic' => 'custom', 'scoring_rules' => $rules('sports_athletics')],
        ['name' => 'Jump', 'code' => 'JUMP', 'participant_type' => 'individual', 'game_mechanic' => 'custom', 'scoring_rules' => $rules('sports_athletics')],

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
        ['code' => 'RUNS', 'title' => 'Ruperto Run 2026', 'day' => 1, 'starts_at' => '06:00', 'ends_at' => '08:00'],
        ['code' => 'CULT-DIGITAL-POSTER', 'title' => 'Visual Art Exhibition', 'day' => 1, 'starts_at' => '08:00', 'ends_at' => '12:00'],
        ['code' => 'CULT-FESTIVAL-PROGRAM', 'title' => 'Parade', 'day' => 1, 'starts_at' => '13:00', 'ends_at' => '14:00'],
        ['code' => 'CULT-FESTIVAL-PROGRAM', 'title' => 'Opening Festival', 'day' => 1, 'starts_at' => '14:00', 'ends_at' => '15:00'],
        ['code' => 'CULT-FEST-DANCE', 'title' => 'Festival Dance Showdown', 'day' => 1, 'starts_at' => '15:00', 'ends_at' => '17:00'],
        ['code' => 'CULT-STUDENT-PAGEANT', 'title' => 'Agility and Sportswear Contest', 'day' => 1, 'starts_at' => '18:00', 'ends_at' => '20:00'],
        ['code' => 'CULT-FESTIVAL-PROGRAM', 'title' => 'Fireworks Display', 'day' => 1, 'starts_at' => '20:00', 'ends_at' => '21:00'],
        ['code' => 'CULT-DIGITAL-POSTER', 'title' => 'Visual Art Contest', 'day' => 2, 'starts_at' => '08:00', 'ends_at' => '12:00'],
        ['code' => 'CULT-SING-SOLO', 'title' => 'Singswerte Solo Random Song Challenge', 'day' => 2, 'starts_at' => '13:00', 'ends_at' => '14:30'],
        ['code' => 'CULT-SING-DUET', 'title' => 'Singswerte Duet Random Song Challenge', 'day' => 2, 'starts_at' => '14:30', 'ends_at' => '16:00'],
        ['code' => 'CULT-SAYAWERTE-DANCE', 'title' => 'Sayawerte Random Group Dancing Challenge', 'day' => 2, 'starts_at' => '16:00', 'ends_at' => '17:30'],
        ['code' => 'CULT-BALAKWERTE', 'title' => 'Balakwerte Random Spoken Word Poetry/Balak', 'day' => 2, 'starts_at' => '17:30', 'ends_at' => '19:00'],
        ['code' => 'CULT-STUDENT-PAGEANT', 'title' => 'Interview and Elegance Round', 'day' => 2, 'starts_at' => '19:00', 'ends_at' => '21:00'],
        ['code' => 'CULT-QUIZ', 'title' => 'Isiphenyo Creative Quiz Challenge', 'day' => 3, 'starts_at' => '08:00', 'ends_at' => '12:00'],
        ['code' => 'CULT-DIGITAL-POSTER', 'title' => 'Visual Arts Showcase', 'day' => 3, 'starts_at' => '13:00', 'ends_at' => '15:00'],
        ['code' => 'CULT-SHORT-FILM', 'title' => 'Short Film Festival', 'day' => 3, 'starts_at' => '15:00', 'ends_at' => '18:00'],
        ['code' => 'CULT-DIGITAL-POSTER', 'title' => 'Visual Arts Judging', 'day' => 4, 'starts_at' => '08:00', 'ends_at' => '12:00'],
        ['code' => 'CULT-FESTIVAL-PROGRAM', 'title' => 'Awarding', 'day' => 5, 'starts_at' => '13:00', 'ends_at' => '17:00'],
    ],
];
