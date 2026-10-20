<?php

use App\Models\Sport;
use App\Services\AuditService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sports')) {
            return;
        }

        DB::transaction(function (): void {
            foreach (Sport::withTrashed()->lockForUpdate()->get()->filter(fn (Sport $sport): bool => $sport->isRelay()) as $sport) {
                $before = ['participant_category' => $sport->participant_category, 'participant_category_label' => $sport->getRawOriginal('participant_category_label')];
                $after = ['participant_category' => 'open', 'participant_category_label' => null];
                if ($before === $after) {
                    continue;
                }

                // Keep unique stored names and IDs intact. The model displays
                // the Open label for legacy divisions without merging results.
                DB::table('sports')->where('id', $sport->id)->update($after);
                if (Schema::hasTable('audit_logs')) {
                    app(AuditService::class)->record('sport.updated', $sport, $before, $after);
                }
            }
        });
    }

    public function down(): void
    {
        // Gender restrictions must not be restored over the open relay roster.
    }
};
