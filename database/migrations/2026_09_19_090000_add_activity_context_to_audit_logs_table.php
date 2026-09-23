<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('actor_role', 20)->nullable()->after('user_id');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('outcome', 20)->default('success')->after('request_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['actor_role', 'user_agent', 'outcome']);
        });
    }
};
