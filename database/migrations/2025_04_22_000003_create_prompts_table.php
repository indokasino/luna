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
        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('content');
            $table->boolean('is_active')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });
        
        // Tambahkan prompt default
        DB::table('prompts')->insert([
            'name' => 'Default',
            'content' => 'Anda adalah asisten AI yang membantu menjawab pertanyaan tentang produk dan layanan kami.',
            'is_active' => true,
            'description' => 'Prompt default untuk GPT-4',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompts');
    }
};