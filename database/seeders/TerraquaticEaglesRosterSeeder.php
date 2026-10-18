<?php

namespace Database\Seeders;

class TerraquaticEaglesRosterSeeder extends TeamRosterSeeder
{
    protected function teamCode(): string
    {
        return 'terraquatic-eagles';
    }

    protected function rosterFile(): string
    {
        return 'terraquatic_eagles';
    }

    protected function fillsMissingFieldsWithNa(): bool
    {
        return true;
    }
}
