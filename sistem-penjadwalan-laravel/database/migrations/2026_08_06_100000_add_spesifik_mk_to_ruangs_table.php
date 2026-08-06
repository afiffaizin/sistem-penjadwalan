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
        Schema::table('ruangs', function (Blueprint $table) {
            $table->string('spesifik_mk')->nullable()->after('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ruangs', function (Blueprint $table) {
            $table->dropColumn('spesifik_mk');
        });
    }
};
