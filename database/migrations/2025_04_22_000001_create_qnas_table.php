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
        Schema::create('qnas', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->text('answer');
            $table->string('tags')->nullable();
            $table->float('confidence_score', 4, 2)->default(1.0);
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');
            $table->timestamps();
            
            // Tambahkan full-text index untuk pencarian
            $table->fullText('question');
            $table->fullText('answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qnas');
    }
};