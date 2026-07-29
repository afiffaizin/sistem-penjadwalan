<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MataKuliah extends Model
{
    protected $fillable = [
        'nama',
        'sks_teori',
        'sks_praktikum',
        'sks_total',
        'kode_group',
        'prodi_id',
        'tahun_ajar_id'
    ];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    public function tahunAjar(): BelongsTo
    {
        return $this->belongsTo(TahunAjar::class);
    }
}
