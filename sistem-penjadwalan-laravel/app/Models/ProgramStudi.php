<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramStudi extends Model
{
    protected $fillable = ['kode', 'nama'];

    public function dosens(): HasMany
    {
        return $this->hasMany(Dosen::class, 'prodi_id');
    }

    public function mata_kuliahs(): HasMany
    {
        return $this->hasMany(MataKuliah::class, 'prodi_id');
    }

    public function ruangs(): HasMany
    {
        return $this->hasMany(Ruang::class, 'prodi_id');
    }
}
