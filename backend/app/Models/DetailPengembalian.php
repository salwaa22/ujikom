<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPengembalian extends Model
{
    protected $table = 'detail_pengembalian';

    protected $fillable = [
        'pengembalian_id',
        'alat_id',
        'jumlah_baik',
        'jumlah_rusak_ringan',
        'jumlah_rusak_berat',
        'jumlah_tidak_lengkap',
        'denda_kerusakan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_baik' => 'integer',
            'jumlah_rusak_ringan' => 'integer',
            'jumlah_rusak_berat' => 'integer',
            'jumlah_tidak_lengkap' => 'integer',
            'denda_kerusakan' => 'integer',
        ];
    }

    public function pengembalian(): BelongsTo
    {
        return $this->belongsTo(Pengembalian::class);
    }

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class);
    }
}