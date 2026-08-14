<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Warehouse extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'stocks')
                    ->withPivot('stock')
                    ->withTimestamps();
    }

    public function supplies(): HasMany
    {
        return $this->hasMany(Supply::class);
    }
}