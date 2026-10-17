<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_together_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained('work_together_sections')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('additional_details')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_url', 2048)->nullable();
            $table->string('layout_type')->default('standard');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['section_id', 'sort_order', 'id'], 'work_cards_section_order_index');
            $table->index(['section_id', 'is_active', 'sort_order'], 'work_cards_section_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_together_cards');
    }
};
