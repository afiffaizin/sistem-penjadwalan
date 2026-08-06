<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ruang extends Model
{
    protected $fillable = ['nama', 'kategori', 'prodi_id', 'tahun_ajar_id', 'spesifik_mk'];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    public function tahunAjar(): BelongsTo
    {
        return $this->belongsTo(TahunAjar::class);
    }
}
