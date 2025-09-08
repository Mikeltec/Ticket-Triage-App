<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            // ULID primary key as specified in requirements
            $table->string('id', 26)->primary(); // ULID is 26 characters
            
            // Core ticket fields
            $table->string('subject');
            $table->text('body');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])
                  ->default('open');
            
            // AI classification fields (nullable since they're set after AI processing)
            $table->string('category')->nullable();
            $table->text('explanation')->nullable();
            $table->decimal('confidence', 3, 2)->nullable(); // 0.00 to 1.00
            
            // Internal staff note
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for performance (filtering, searching, analytics)
            $table->index('status');
            $table->index('category');
            $table->index('created_at');
            $table->index(['status', 'category']); // Composite index for analytics
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};