<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Operation extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'code',
        'name',
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

    public function divisions(): BelongsToMany
    {
        return $this->belongsToMany(Division::class, 'division_operation', 'operation_id', 'division_id');
    }

    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class, 'operation_part', 'operation_id', 'part_id');
    }
}
