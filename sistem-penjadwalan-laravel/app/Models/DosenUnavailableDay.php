<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DosenUnavailableDay extends Model
{
    protected $fillable = [
        'user_id',
        'dosen_id',
        'prodi_id',
        'tahun_ajar_id',
        'hari',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    public function tahunAjar(): BelongsTo
    {
        return $this->belongsTo(TahunAjar::class, 'tahun_ajar_id');
    }
}
