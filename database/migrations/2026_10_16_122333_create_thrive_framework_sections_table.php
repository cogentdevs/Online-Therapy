<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thrive_framework_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('language', 10)->unique();
            $table->string('eyebrow');
            $table->string('heading');
            $table->text('description_one')->nullable();
            $table->text('description_two')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thrive_framework_sections');
    }
};
