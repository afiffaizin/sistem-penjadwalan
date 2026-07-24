<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dosens', function (Blueprint $table) {
            $table->foreignId('tahun_ajar_id')->nullable()->after('nip')
                  ->constrained('tahun_ajars')->cascadeOnDelete();
        });

        Schema::table('mata_kuliahs', function (Blueprint $table) {
            $table->foreignId('tahun_ajar_id')->nullable()->after('prodi_id')
                  ->constrained('tahun_ajars')->cascadeOnDelete();
        });

        Schema::table('ruangs', function (Blueprint $table) {
            $table->foreignId('tahun_ajar_id')->nullable()->after('prodi_id')
                  ->constrained('tahun_ajars')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dosens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tahun_ajar_id');
        });

        Schema::table('mata_kuliahs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tahun_ajar_id');
        });

        Schema::table('ruangs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tahun_ajar_id');
        });
    }
};
