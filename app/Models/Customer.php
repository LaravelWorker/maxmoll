<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}