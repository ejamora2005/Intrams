<?php

namespace Database\Seeders;

class TrojanWarriorsRosterSeeder extends TeamRosterSeeder
{
    protected function teamCode(): string
    {
        return 'trojan-warriors';
    }

    protected function rosterFile(): string
    {
        return 'trojan_warriors';
    }

    protected function pairKey(string $name, string $sportCode): ?string
    {
        return match ([$name, $sportCode]) {
            ['Romel Pitong', 'TABLE-TENNIS-DOUBLES-MEN'],
            ['Earl Lorence Maraña', 'TABLE-TENNIS-DOUBLES-MEN'] => 'trojan-tt-men-doubles-1',
            ['Eva Mae Cabilic', 'TABLE-TENNIS-DOUBLES-WOMEN'],
            ['Joanna Mae Maraña', 'TABLE-TENNIS-DOUBLES-WOMEN'] => 'trojan-tt-women-doubles-1',
            default => null,
        };
    }
}
