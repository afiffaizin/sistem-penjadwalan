<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dosen extends Model
{
    protected $fillable = ['kode_dosen', 'nama', 'nidn', 'prodi_id', 'nip'];

    public function prodis()
    {
        return $this->belongsToMany(ProgramStudi::class, 'dosen_prodi', 'dosen_id', 'prodi_id');
    }

    public function unavailableDays(): HasMany
    {
        return $this->hasMany(DosenUnavailableDay::class);
    }
}
