<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->string('level', 32)->nullable()->after('duration_value');
            $table->string('instructor_name')->nullable()->after('level');
            $table->text('instructor_bio')->nullable()->after('instructor_name');
            $table->string('instructor_image')->nullable()->after('instructor_bio');
            $table->boolean('certificate_available')->default(false)->after('instructor_image');
            $table->string('access_type', 32)->nullable()->after('certificate_available');
            $table->text('access_description')->nullable()->after('access_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn(['level', 'instructor_name', 'instructor_bio', 'instructor_image', 'certificate_available', 'access_type', 'access_description']);
        });
    }
};
