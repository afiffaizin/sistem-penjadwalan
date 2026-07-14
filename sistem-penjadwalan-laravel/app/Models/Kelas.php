<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kelas extends Model
{
    protected $fillable = ['nama', 'prodi_id', 'tahun_ajar_id'];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    public function tahun_ajar(): BelongsTo
    {
        return $this->belongsTo(TahunAjar::class, 'tahun_ajar_id');
    }
}
