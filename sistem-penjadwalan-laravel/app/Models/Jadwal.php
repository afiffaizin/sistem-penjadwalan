<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jadwal extends Model
{
    protected $fillable = [
        'tahun_ajar_id',
        'dosen_id',
        'mata_kuliah_id',
        'kelas_id',
        'ruang_id',
        'hari',
        'sesi_mulai',
        'sesi_selesai'
    ];

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
    public function mata_kuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }
    public function ruang(): BelongsTo
    {
        return $this->belongsTo(Ruang::class);
    }
    public function tahun_ajar(): BelongsTo
    {
        return $this->belongsTo(TahunAjar::class);
    }
}
