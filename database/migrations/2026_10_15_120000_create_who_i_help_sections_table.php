<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('who_i_help_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('language')->unique();
            $table->string('eyebrow', 100);
            $table->string('heading');
            $table->longText('description');
            $table->longText('bottom_message');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('who_i_help_sections');
    }
};
