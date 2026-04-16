<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartSerialNumber extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'area_id',
        'part_id',
        'serial_number',
        'status',
    ];

    protected $casts = [
        'area_id' => 'integer',
        'part_id' => 'integer',
        'status' => 'integer',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SerialNumberLog::class, 'part_serial_number_id');
    }
}
