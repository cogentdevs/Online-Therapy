<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_together_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('language', 10)->unique();
            $table->string('eyebrow');
            $table->string('heading');
            $table->text('description')->nullable();
            $table->text('bottom_message')->nullable();
            $table->string('bottom_button_text')->nullable();
            $table->string('bottom_button_url', 2048)->nullable();
            $table->unsignedTinyInteger('cards_per_row')->default(3);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_together_sections');
    }
};
