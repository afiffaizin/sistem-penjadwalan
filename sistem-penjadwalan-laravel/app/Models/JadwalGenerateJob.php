<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalGenerateJob extends Model
{
    protected $fillable = ['tahun_ajar_id', 'status', 'error_message', 'started_at', 'completed_at'];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tahunAjar(): BelongsTo
    {
        return $this->belongsTo(TahunAjar::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public static function hasActiveJob(int $tahunAjarId): bool
    {
        return static::where('tahun_ajar_id', $tahunAjarId)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();
    }

    public static function latestForTahunAjar(int $tahunAjarId): ?self
    {
        return static::where('tahun_ajar_id', $tahunAjarId)
            ->latest()
            ->first();
    }
}
