<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('who_i_help_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained('who_i_help_sections')->cascadeOnDelete();
            $table->string('title', 1000);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['section_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('who_i_help_items');
    }
};
