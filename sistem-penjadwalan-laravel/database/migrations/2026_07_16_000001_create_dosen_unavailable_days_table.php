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
        Schema::create('dosen_unavailable_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosens')->cascadeOnDelete();
            $table->foreignId('prodi_id')->constrained('program_studis')->cascadeOnDelete();
            $table->foreignId('tahun_ajar_id')->constrained('tahun_ajars')->cascadeOnDelete();
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);
            $table->timestamps();

            $table->unique(['dosen_id', 'tahun_ajar_id', 'hari'], 'dosen_unavailable_unique');
            $table->index(['prodi_id', 'tahun_ajar_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dosen_unavailable_days');
    }
};
