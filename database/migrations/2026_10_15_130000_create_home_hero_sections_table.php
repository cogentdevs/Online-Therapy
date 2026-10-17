<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_hero_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('language')->unique();
            $table->string('eyebrow', 255);
            $table->text('heading_before_highlight');
            $table->string('highlighted_text', 255)->nullable();
            $table->text('heading_after_highlight')->nullable();
            $table->longText('description')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('image_alt', 255)->nullable();
            $table->string('primary_button_text', 255)->nullable();
            $table->string('primary_button_url', 2048)->nullable();
            $table->string('secondary_button_text', 255)->nullable();
            $table->string('secondary_button_url', 2048)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_hero_sections');
    }
};
