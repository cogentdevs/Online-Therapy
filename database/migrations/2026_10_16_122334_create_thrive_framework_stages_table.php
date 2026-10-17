<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thrive_framework_stages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained('thrive_framework_sections')->cascadeOnDelete();
            $table->string('letter', 10);
            $table->string('stage_label', 50)->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['section_id', 'sort_order', 'id'], 'thrive_stages_section_order_index');
            $table->index(['section_id', 'is_active', 'sort_order'], 'thrive_stages_section_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thrive_framework_stages');
    }
};
