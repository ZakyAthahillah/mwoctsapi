<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reason extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'code',
        'name',
        'division_id',
        'status',
    ];

    protected $casts = [
        'area_id' => 'integer',
        'division_id' => 'integer',
        'status' => 'integer',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function divisions(): BelongsToMany
    {
        return $this->belongsToMany(Division::class, 'division_reason', 'reason_id', 'division_id');
    }

    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class, 'part_reason', 'reason_id', 'part_id');
    }
}
