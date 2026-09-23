<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('school_year')->constrained('courses')->nullOnDelete();
        });
    }
    public function down(): void { Schema::table('students', fn (Blueprint $table) => $table->dropConstrainedForeignId('course_id')); Schema::dropIfExists('courses'); }
};
