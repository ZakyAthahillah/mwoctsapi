<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'code',
        'name',
        'description',
        'image',
        'image_side',
        'status',
    ];

    protected $casts = [
        'area_id' => 'integer',
        'status' => 'integer',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'machine_position', 'machine_id', 'position_id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(MachinePart::class, 'machine_id');
    }

    public function partsSide(): HasMany
    {
        return $this->hasMany(MachinePartSide::class, 'machine_id');
    }
}
