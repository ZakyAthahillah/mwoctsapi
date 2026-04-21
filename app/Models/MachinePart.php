<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachinePart extends Model
{
    protected $table = 'machine_parts';

    protected $fillable = [
        'machine_id',
        'part_id',
        'sort_order',
        'pos_x',
        'pos_y',
    ];

    public $timestamps = false;

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
