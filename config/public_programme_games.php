<?php

// The supplied Schedule-Games-2026.txt updates only these public sessions.
// The user confirmed that October 23 Modified Volleyball is 11 AM, not 11 PM.
$eagles = 'team:terraquatic-eagles';
$dragons = 'team:sea-dragons';
$warriors = 'team:trojan-warriors';
$series = fn ($home, $away, $third) => [
    ['home' => $home, 'away' => $away],
    ['home' => 'loser:1', 'away' => $third],
    ['home' => 'winner:1', 'away' => $third],
];

return [
    'id' => 'schedule-games-2026-10-20',
    'filename' => 'Schedule-Games-2026.txt',
    'events' => [
        'E12:E15' => ['event' => 'Volleyball M/W', 'date' => '2026-10-21', 'session' => 'PM', 'starts_at' => '12:00', 'venue' => 'MPC', 'games' => $series($eagles, $dragons, $warriors)],
        'F15' => ['event' => 'Table Tennis M/W (Team and Doubles)', 'date' => '2026-10-21', 'session' => 'PM', 'starts_at' => '12:00', 'venue' => 'SSC', 'games' => $series($dragons, $eagles, $warriors)],
        'F12' => ['event' => 'Relay M/W (100m, 400m)', 'date' => '2026-10-21', 'session' => 'PM', 'starts_at' => '13:00', 'venue' => 'Open Field'],
        'F13' => ['event' => 'Jumps M/W', 'date' => '2026-10-21', 'session' => 'PM', 'starts_at' => '13:00', 'venue' => 'Open Field'],
        'F14' => ['event' => 'Throws M/W', 'date' => '2026-10-21', 'session' => 'PM', 'starts_at' => '13:00', 'venue' => 'Open Field'],
        'I6' => ['event' => 'Mobile Legends M/W', 'date' => '2026-10-22', 'session' => 'AM', 'starts_at' => '08:00', 'venue' => 'CompLab'],
        'G6:G9' => ['event' => 'Basketball 5x5 M/W', 'date' => '2026-10-22', 'session' => 'AM', 'starts_at' => '08:00', 'venue' => 'MPC', 'games' => $series($eagles, $dragons, $warriors)],
        'H6:H8' => ['event' => 'Beach Volleyball M/W', 'date' => '2026-10-22', 'session' => 'AM', 'starts_at' => '08:00', 'venue' => 'Open Field', 'games' => $series($warriors, $eagles, $dragons)],
        'C12:C13' => ['event' => 'Russian Softball', 'date' => '2026-10-22', 'session' => 'PM', 'starts_at' => '13:00', 'venue' => 'Baseball Diamond', 'games' => $series($warriors, $eagles, $dragons)],
        'J6' => ['event' => 'Badminton M/W (Singles)', 'date' => '2026-10-23', 'session' => 'AM', 'starts_at' => '07:00', 'venue' => 'MPC'],
        'J7' => ['event' => 'Badminton M/W (Doubles)', 'date' => '2026-10-23', 'session' => 'AM', 'starts_at' => '09:00', 'venue' => 'MPC', 'games' => $series($dragons, $eagles, $warriors)],
        'J8' => ['event' => 'Modified Volleyball', 'date' => '2026-10-23', 'session' => 'AM', 'starts_at' => '11:00', 'venue' => 'MPC', 'games' => $series($dragons, $eagles, $warriors)],
        'J12' => ['event' => 'Modified Volleyball (Employee)', 'date' => '2026-10-23', 'session' => 'PM', 'starts_at' => '13:00', 'venue' => 'MPC', 'games' => $series($dragons, $eagles, $warriors)],
        // The new game series covers the former separate AM/PM placeholders.
        'G12:G14' => ['is_published' => false],
        'H12:H14' => ['is_published' => false],
    ],
];
