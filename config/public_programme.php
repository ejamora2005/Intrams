<?php

// Public programme only. These entries do not create or edit sports, matches,
// saved competition schedules, participants, or scoring records.
return [
    'school_year' => '2026-2027',
    'starts_on' => '2026-10-19',
    'ends_on' => '2026-10-23',
    'source' => [
        'url' => 'https://docs.google.com/spreadsheets/d/17t4oYvvcd8G4zvpNOMqvOAlnSUXseUJMC9Vx2cWHfUg/edit?gid=1781504657#gid=1781504657',
        'worksheet' => 'Sked',
        'range' => 'A3:L19',
        'snapshot_date' => '2026-10-20',
    ],
    // Consecutive repeated cells form one session/venue block. Blank cells and
    // "x" markers are not events. No start hour is invented for AM/PM entries.
    'events' => [
        ['date' => '2026-10-19', 'session' => 'AM', 'event' => 'Badminton', 'venue' => null, 'cells' => 'A6:A9'],
        ['date' => '2026-10-19', 'session' => 'PM', 'event' => 'Badminton', 'venue' => null, 'ends_at' => '15:00', 'cells' => 'A12:A15'],

        ['date' => '2026-10-20', 'session' => 'AM', 'event' => 'Basketball 3x3 (MW)', 'venue' => 'GYM', 'cells' => 'B6:B9'],
        ['date' => '2026-10-20', 'session' => 'AM', 'event' => 'Baseballl / Runs M/W', 'venue' => 'FIELD', 'cells' => 'C6:C9'],
        ['date' => '2026-10-20', 'session' => 'AM', 'event' => 'Chess', 'venue' => 'Library', 'cells' => 'D6'],
        ['date' => '2026-10-20', 'session' => 'PM', 'event' => 'Basketball 3x3 (MW)', 'venue' => 'GYM', 'ends_at' => '14:00', 'cells' => 'B12:B15'],
        ['date' => '2026-10-20', 'session' => 'PM', 'event' => 'Russian Softball', 'venue' => 'FIELD', 'cells' => 'C12:C13'],
        ['date' => '2026-10-20', 'session' => 'PM', 'event' => 'Alumni Games', 'venue' => 'FIELD', 'category' => 'activities', 'cells' => 'C15'],
        ['date' => '2026-10-20', 'session' => 'PM', 'event' => 'Prep for Mr and Ms', 'venue' => 'GYM', 'category' => 'activities', 'cells' => 'B16'],

        ['date' => '2026-10-21', 'session' => 'AM', 'event' => 'Isip Henyo', 'venue' => 'GYM', 'category' => 'cultural', 'cells' => 'E6'],
        ['date' => '2026-10-21', 'session' => 'AM', 'event' => 'Treasure', 'venue' => 'GYM', 'category' => 'cultural', 'cells' => 'E7'],
        ['date' => '2026-10-21', 'session' => 'PM', 'event' => 'Volleyball M/W', 'venue' => 'GYM', 'cells' => 'E12:E15'],
        ['date' => '2026-10-21', 'session' => 'PM', 'event' => 'Relay MW Mixed', 'venue' => 'FIELD', 'cells' => 'F12'],
        ['date' => '2026-10-21', 'session' => 'PM', 'event' => 'Jumps MW', 'venue' => 'FIELD', 'cells' => 'F13'],
        ['date' => '2026-10-21', 'session' => 'PM', 'event' => 'Throws MW', 'venue' => 'FIELD', 'cells' => 'F14'],
        ['date' => '2026-10-21', 'session' => 'PM', 'event' => 'Table Tennis', 'venue' => 'FIELD', 'cells' => 'F15'],
        ['date' => '2026-10-21', 'session' => 'Evening', 'event' => 'Awarding Mugna', 'venue' => 'GYM', 'category' => 'activities', 'starts_at' => '19:00', 'cells' => 'E18:E19'],

        ['date' => '2026-10-22', 'session' => 'AM', 'event' => 'Basketball 5x5 M/W', 'venue' => 'GYM', 'cells' => 'G6:G9'],
        ['date' => '2026-10-22', 'session' => 'AM', 'event' => 'Beach M/W', 'venue' => 'FIELD', 'cells' => 'H6:H8'],
        ['date' => '2026-10-22', 'session' => 'AM', 'event' => 'ML', 'venue' => 'CompLab', 'cells' => 'I6'],
        ['date' => '2026-10-22', 'session' => 'PM', 'event' => 'Basketball 5x5 M/W', 'venue' => 'GYM', 'cells' => 'G12:G14'],
        ['date' => '2026-10-22', 'session' => 'PM', 'event' => 'Beach M/W', 'venue' => 'FIELD', 'cells' => 'H12:H14'],
        ['date' => '2026-10-22', 'session' => 'PM', 'event' => 'Alumni Games', 'venue' => 'GYM', 'category' => 'activities', 'cells' => 'G16'],

        ['date' => '2026-10-23', 'session' => 'AM', 'event' => 'Badminton (Indi)', 'venue' => 'GYM', 'cells' => 'J6'],
        ['date' => '2026-10-23', 'session' => 'AM', 'event' => 'ML', 'venue' => 'CompLab', 'cells' => 'L6'],
        ['date' => '2026-10-23', 'session' => 'AM', 'event' => 'Badminton (Doubles)', 'venue' => 'GYM', 'cells' => 'J7'],
        ['date' => '2026-10-23', 'session' => 'AM', 'event' => 'MODIFIED', 'venue' => 'GYM', 'cells' => 'J8'],
        ['date' => '2026-10-23', 'session' => 'PM', 'event' => 'MODIFIED', 'venue' => 'GYM', 'cells' => 'J12'],
    ],
];
