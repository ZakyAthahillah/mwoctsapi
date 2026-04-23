<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'code',
        'name',
        'description',
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

    public function operations(): BelongsToMany
    {
        return $this->belongsToMany(Operation::class, 'operation_part', 'part_id', 'operation_id');
    }

    public function reasons(): BelongsToMany
    {
        return $this->belongsToMany(Reason::class, 'part_reason', 'part_id', 'reason_id');
    }

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(PartSerialNumber::class, 'part_id');
    }
}
