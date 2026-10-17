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
        Schema::table('thrive_framework_sections', function (Blueprint $table): void {
            $table->unsignedTinyInteger('cards_per_row')->default(3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thrive_framework_sections', function (Blueprint $table): void {
            $table->dropColumn('cards_per_row');
        });
    }
};
