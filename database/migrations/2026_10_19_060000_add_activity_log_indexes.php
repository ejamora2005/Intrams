<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index(['created_at', 'id']);
            $table->index('request_id');
            $table->index(['actor_role', 'created_at']);
            $table->index(['outcome', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex(['created_at', 'id']);
            $table->dropIndex(['request_id']);
            $table->dropIndex(['actor_role', 'created_at']);
            $table->dropIndex(['outcome', 'created_at']);
        });
    }
};
