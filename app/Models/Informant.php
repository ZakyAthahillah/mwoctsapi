<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Informant extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'code',
        'name',
        'status',
        'group_id',
    ];

    protected $casts = [
        'area_id' => 'integer',
        'status' => 'integer',
        'group_id' => 'integer',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
