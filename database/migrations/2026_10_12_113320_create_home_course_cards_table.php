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
        Schema::create('home_course_cards', function (Blueprint $table): void {
            $table->id();
            $table->string('language')->index();
            $table->foreignId('course_id')->unique()->constrained('courses')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('short_description')->nullable();
            $table->string('button_label', 100)->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_admin_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['language', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_course_cards');
    }
};
