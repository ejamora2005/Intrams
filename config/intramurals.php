<?php

return [
    'default_competitions' => [
        ['name' => 'Basketball 3x3', 'code' => 'BASKET-3X3'],
        ['name' => 'Basketball 5x5', 'code' => 'BASKET-5X5'],
        ['name' => 'Volleyball', 'code' => 'VOLLEYBALL'],
        ['name' => 'Badminton', 'code' => 'BADMINTON', 'participant_type' => 'dual'],
        ['name' => 'Table Tennis', 'code' => 'TABLE-TENNIS', 'participant_type' => 'individual'],
        ['name' => 'Baseball', 'code' => 'BASEBALL'],
        ['name' => 'Softball', 'code' => 'SOFTBALL'],
        ['name' => 'Russian Softball', 'code' => 'RUSSIAN-SOFTBALL'],

        [
            'name' => 'Cultural: Festival Dance Competition',
            'code' => 'CULT-FEST-DANCE',
            'description' => 'Cultural event. Students: 40 minimum; props/propsmen excluded. Employees: none.',
            'rules' => 'Students: 40 minimum; props/propsmen excluded. Employees: none.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 25.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 20.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 15.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Faction Pakulo',
            'code' => 'CULT-FACTION-PAKULO',
            'description' => 'Cultural event. Students: not specified. Employees: encouraged.',
            'rules' => 'Students: not specified. Employees: encouraged.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 15.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 10.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 7.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Sayawerte Random Group Dancing Challenge',
            'code' => 'CULT-SAYAWERTE-DANCE',
            'description' => 'Cultural event. Students: 6 minimum, 8 maximum. Employees: 5 minimum, 8 maximum.',
            'rules' => 'Students: 6 minimum, 8 maximum. Employees: 5 minimum, 8 maximum.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 15.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 10.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 5.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Intramural 2026 Student Festival Pageant',
            'code' => 'CULT-STUDENT-PAGEANT',
            'description' => 'Cultural event. Maximum of 8 candidates: 3 males, 3 females, 1 LGBTQIA+ male, and 1 LGBTQIA+ female. Employees: none.',
            'rules' => 'Maximum of 8 candidates: 3 males, 3 females, 1 LGBTQIA+ male, and 1 LGBTQIA+ female. Employees: none.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Major title', 'points' => 15.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up title', 'points' => 10.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up title', 'points' => 5.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Digital Poster Contest',
            'code' => 'CULT-DIGITAL-POSTER',
            'description' => 'Cultural event. Maximum 2 students: 1 male and 1 female. Employees: none.',
            'rules' => 'Maximum 2 students: 1 male and 1 female. Employees: none.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 10.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 7.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 5.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Infographics Making Contest',
            'code' => 'CULT-INFOGRAPHICS',
            'description' => 'Cultural event. Maximum 2 students: 1 male and 1 female. Employees: none.',
            'rules' => 'Maximum 2 students: 1 male and 1 female. Employees: none.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 10.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 7.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 5.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Digital Photography',
            'code' => 'CULT-DIGI-PHOTO',
            'description' => 'Cultural event. Maximum 2 students: 1 male and 1 female. Employees: none.',
            'rules' => 'Maximum 2 students: 1 male and 1 female. Employees: none.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 10.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 7.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 5.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Collaborative Painting',
            'code' => 'CULT-COLLAB-PAINT',
            'description' => 'Cultural event. Students: no limit specified. Employees: none.',
            'rules' => 'Students: no limit specified. Employees: none.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 15.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 10.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 7.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Pencil Drawing',
            'code' => 'CULT-PENCIL-DRAWING',
            'description' => 'Cultural event. Maximum 2 students: 1 male and 1 female. Employees: none.',
            'rules' => 'Maximum 2 students: 1 male and 1 female. Employees: none.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 10.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 7.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 5.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Charcoal Rendering',
            'code' => 'CULT-CHARCOAL',
            'description' => 'Cultural event. Maximum 2 students: 1 male and 1 female. Employees: none.',
            'rules' => 'Maximum 2 students: 1 male and 1 female. Employees: none.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 10.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 7.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 5.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Short Film - Last na ni!',
            'code' => 'CULT-SHORT-FILM',
            'description' => 'Cultural event. 4-8 members with 1:1 male-to-female ratio. Employees may participate in any role.',
            'rules' => '4-8 members with 1:1 male-to-female ratio. Employees may participate in any role.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 15.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 10.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 7.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Singswerte Solo Random Song Challenge',
            'code' => 'CULT-SING-SOLO',
            'description' => 'Cultural event. Up to 3 student contestants. Employees: up to 2 contestants.',
            'rules' => 'Up to 3 student contestants. Employees: up to 2 contestants.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 10.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 7.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 5.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Singswerte Duet Random Song Challenge',
            'code' => 'CULT-SING-DUET',
            'description' => 'Cultural event. Up to 2 student pairs. Employees: 1 pair.',
            'rules' => 'Up to 2 student pairs. Employees: 1 pair.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 10.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 7.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 5.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Balakwerte Spoken Word Poetry/Balak Challenge',
            'code' => 'CULT-BALAKWERTE',
            'description' => 'Cultural event. 1 contestant or team of up to 3 members. Employees: none.',
            'rules' => '1 contestant or team of up to 3 members. Employees: none.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 10.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 7.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 5.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Mash-Up Gen Z Balitaw',
            'code' => 'CULT-MASHUP-BALITAW',
            'description' => 'Cultural event. 2 principal performers; supporting performers may be included. Employees: none.',
            'rules' => '2 principal performers; supporting performers may be included. Employees: none.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 10.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 7.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 5.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Isiphenyo Creative Quiz Challenge',
            'code' => 'CULT-QUIZ',
            'description' => 'Cultural event. Students: 4 students. Employees: 2 coaches.',
            'rules' => 'Students: 4 students. Employees: 2 coaches.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 15.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 10.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 7.0, 'medal' => 'bronze'],
                ],
            ],
        ],
        [
            'name' => 'Cultural: Treasurehenyo Creative Survival Treasure Hunt',
            'code' => 'CULT-TREASURE',
            'description' => 'Cultural event. Students: 10, with 5 males and 5 females. Employees: 5.',
            'rules' => 'Students: 10, with 5 males and 5 females. Employees: 5.',
            'game_mechanic' => 'custom',
            'scoring_rules' => [
                'placements' => [
                    ['placement' => 1, 'label' => 'Champion', 'points' => 15.0, 'medal' => 'gold'],
                    ['placement' => 2, 'label' => '1st Runner-up', 'points' => 10.0, 'medal' => 'silver'],
                    ['placement' => 3, 'label' => '2nd Runner-up', 'points' => 7.0, 'medal' => 'bronze'],
                ],
            ],
        ],

        ['name' => 'Cultural Bonus: Festival Dance - Most Number of Participants', 'code' => 'CULT-BONUS-FD-PART', 'description' => 'Additional cultural points for Festival Dance Competition.', 'rules' => 'Special award: Most Number of Participants.', 'game_mechanic' => 'custom', 'scoring_rules' => ['placements' => [['placement' => 1, 'label' => 'Most Number of Participants', 'points' => 2.0, 'medal' => 'none']]]],
        ['name' => 'Cultural Bonus: Festival Dance - Best in Costume', 'code' => 'CULT-BONUS-FD-COSTUME', 'description' => 'Additional cultural points for Festival Dance Competition.', 'rules' => 'Special award: Best in Costume.', 'game_mechanic' => 'custom', 'scoring_rules' => ['placements' => [['placement' => 1, 'label' => 'Best in Costume', 'points' => 3.0, 'medal' => 'none']]]],
        ['name' => 'Cultural Bonus: Festival Dance - Best Streetdance/Parade Performance', 'code' => 'CULT-BONUS-FD-STREET', 'description' => 'Additional cultural points for Festival Dance Competition.', 'rules' => 'Special award: Best Streetdance/Parade Performance.', 'game_mechanic' => 'custom', 'scoring_rules' => ['placements' => [['placement' => 1, 'label' => 'Best Streetdance/Parade Performance', 'points' => 5.0, 'medal' => 'none']]]],
        ['name' => 'Cultural Bonus: Faction Pakulo - Most Number of Participants', 'code' => 'CULT-BONUS-FP-PART', 'description' => 'Additional cultural points for Faction Pakulo.', 'rules' => 'Special award: Most Number of Participants.', 'game_mechanic' => 'custom', 'scoring_rules' => ['placements' => [['placement' => 1, 'label' => 'Most Number of Participants', 'points' => 2.0, 'medal' => 'none']]]],
        ['name' => 'Cultural Bonus: Short Film - Best Directing', 'code' => 'CULT-BONUS-FILM-DIRECT', 'description' => 'Additional cultural points for Short Film.', 'rules' => 'Special award: Best Directing.', 'game_mechanic' => 'custom', 'scoring_rules' => ['placements' => [['placement' => 1, 'label' => 'Best Directing', 'points' => 3.0, 'medal' => 'none']]]],
        ['name' => 'Cultural Bonus: Short Film - Best Musical Score', 'code' => 'CULT-BONUS-FILM-SCORE', 'description' => 'Additional cultural points for Short Film.', 'rules' => 'Special award: Best Musical Score.', 'game_mechanic' => 'custom', 'scoring_rules' => ['placements' => [['placement' => 1, 'label' => 'Best Musical Score', 'points' => 2.0, 'medal' => 'none']]]],
        ['name' => 'Cultural Bonus: Short Film - Best Portrayal in a Leading Role', 'code' => 'CULT-BONUS-FILM-ACT', 'description' => 'Additional cultural points for Short Film.', 'rules' => 'Special award: Best Portrayal in a Leading Role.', 'game_mechanic' => 'custom', 'scoring_rules' => ['placements' => [['placement' => 1, 'label' => 'Best Portrayal in a Leading Role', 'points' => 2.0, 'medal' => 'none']]]],
        ['name' => 'Cultural Bonus: Singswerte Solo - Finalist', 'code' => 'CULT-BONUS-SING-FINAL', 'description' => 'Additional cultural points for Singswerte Solo Random Song Challenge.', 'rules' => 'Special award: Finalist.', 'game_mechanic' => 'custom', 'scoring_rules' => ['placements' => [['placement' => 1, 'label' => 'Finalist', 'points' => 2.0, 'medal' => 'none']]]],
        ['name' => 'Cultural Bonus: Mash-Up Gen Z Balitaw - Best Musical Mash-Up', 'code' => 'CULT-BONUS-MASHUP', 'description' => 'Additional cultural points for Mash-Up Gen Z Balitaw.', 'rules' => 'Special award: Best Musical Mash-Up.', 'game_mechanic' => 'custom', 'scoring_rules' => ['placements' => [['placement' => 1, 'label' => 'Best Musical Mash-Up', 'points' => 3.0, 'medal' => 'none']]]],
    ],
];
