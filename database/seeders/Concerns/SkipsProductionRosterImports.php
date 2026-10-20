<?php

namespace Database\Seeders\Concerns;

trait SkipsProductionRosterImports
{
    protected function skipsProductionRosterImport(): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        $this->command?->warn('Bundled roster import skipped in production. Manage live players in Admin or GAM; local roster files must not overwrite the Dokploy database.');

        return true;
    }
}
