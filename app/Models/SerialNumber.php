<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerialNumber extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'area_id',
        'machine_id',
        'position_id',
        'part_id',
        'part_serial_number_id',
    ];

    protected $casts = [
        'area_id' => 'integer',
        'machine_id' => 'integer',
        'position_id' => 'integer',
        'part_id' => 'integer',
        'part_serial_number_id' => 'integer',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function partSerialNumber(): BelongsTo
    {
        return $this->belongsTo(PartSerialNumber::class, 'part_serial_number_id');
    }
}
