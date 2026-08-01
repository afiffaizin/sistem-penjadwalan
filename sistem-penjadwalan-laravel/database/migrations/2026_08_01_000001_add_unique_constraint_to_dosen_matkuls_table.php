<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dosen_matkuls', function (Blueprint $table) {
            $table->unique(
                ['dosen_id', 'mata_kuliah_id', 'kelas_id', 'tahun_ajar_id'],
                'dosen_matkul_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('dosen_matkuls', function (Blueprint $table) {
            $table->dropUnique('dosen_matkul_unique');
        });
    }
};
