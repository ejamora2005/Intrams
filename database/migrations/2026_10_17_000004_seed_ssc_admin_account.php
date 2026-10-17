<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Keep the SSC account available on deployments that run migrations but
     * do not invoke the full database seeder. The password remains controlled
     * by the same environment variable as the other operations accounts.
     */
    public function up(): void
    {
        $password = env('INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD');

        if (! is_string($password) || $password === '') {
            return;
        }

        $user = User::query()->firstOrNew(['email' => 'ssc@example.com']);
        $user->forceFill([
            'name' => 'Supreme Student Council',
            'email_verified_at' => $user->email_verified_at ?? now(),
            'role' => 'admin',
            'status' => 'active',
            'managed_team_id' => null,
        ]);

        if (! $user->exists || ! Hash::check($password, (string) $user->password)) {
            $user->password = Hash::make($password);
        }

        $user->save();
    }

    public function down(): void
    {
        User::query()->where('email', 'ssc@example.com')->delete();
    }
};
