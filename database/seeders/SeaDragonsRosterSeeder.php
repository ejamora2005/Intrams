<?php

namespace Database\Seeders;

class SeaDragonsRosterSeeder extends TeamRosterSeeder
{
    protected function teamCode(): string
    {
        return 'sea-dragons';
    }

    protected function rosterFile(): string
    {
        return 'sea_dragons';
    }

    protected function fillsMissingFieldsWithNa(): bool
    {
        return true;
    }
}
