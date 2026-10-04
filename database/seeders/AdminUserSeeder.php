<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Create or reset the local development operations accounts.
     */
    public function run(): void
    {
        $password = env('INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD');

        if (! is_string($password) || $password === '') {
            $this->command?->warn('Skipping default admin, GAM, and Tabulator accounts. Set INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD in .env to seed them.');

            return;
        }

        $defaultGamTeamId = Team::query()
            ->where('status', 'active')
            ->whereHas('edition', fn ($query) => $query->where('status', 'active'))
            ->orderBy('name')
            ->value('id')
            ?: Team::query()->where('status', 'active')->orderBy('name')->value('id');

        foreach ([
            ['name' => 'System Administrator', 'email' => 'admin@example.com', 'role' => 'admin'],
            ['name' => 'General Athletics Manager', 'email' => 'gam@example.com', 'role' => 'gam'],
            ['name' => 'Event Tabulator', 'email' => 'tabulator@example.com', 'role' => 'tabulator'],
        ] as $account) {
            $user = User::query()->firstOrNew(['email' => $account['email']]);

            $user->forceFill([
                'name' => $account['name'],
                'email_verified_at' => $user->email_verified_at ?? now(),
                'role' => $account['role'],
                'status' => 'active',
                'managed_team_id' => $account['role'] === 'gam'
                    ? ($user->managed_team_id ?: $defaultGamTeamId)
                    : null,
            ]);

            if (! $user->exists || ! Hash::check($password, (string) $user->password)) {
                $user->password = Hash::make($password);
            }

            $user->save();
        }
    }
}
