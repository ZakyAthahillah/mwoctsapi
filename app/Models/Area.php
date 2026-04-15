<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'object_name',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];
}
