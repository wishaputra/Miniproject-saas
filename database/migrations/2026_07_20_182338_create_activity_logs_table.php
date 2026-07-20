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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // User who performed the action
            $table->string('action'); // created, updated, deleted, reassigned
            
            // Polymorphic relation to loggable model (Task, Project)
            $table->nullableMorphs('loggable');
            
            $table->text('description')->nullable(); // e.g. "Admin created a new task"
            $table->json('old_values')->nullable(); // Store previous state if updated
            $table->json('new_values')->nullable(); // Store new state
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
