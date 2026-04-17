<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fbdt extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'area_id',
        'tahun',
        'bulan',
        'fb',
        'dt',
        'mtbf',
        'mttr',
    ];

    protected $casts = [
        'area_id' => 'integer',
        'tahun' => 'integer',
        'bulan' => 'integer',
        'fb' => 'float',
        'dt' => 'float',
        'mtbf' => 'float',
        'mttr' => 'float',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
