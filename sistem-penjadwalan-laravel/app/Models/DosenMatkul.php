<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DosenMatkul extends Model
{
    protected $fillable = ['dosen_id', 'mata_kuliah_id', 'kelas_id', 'tahun_ajar_id'];

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
    public function tahun_ajar(): BelongsTo
    {
        return $this->belongsTo(TahunAjar::class);
    }
}
