<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'positions',
        'min_players',
        'max_players',
        'is_active',
    ];

    protected $casts = [
        'positions' => 'array',
        'is_active' => 'boolean',
        'min_players' => 'integer',
        'max_players' => 'integer',
    ];

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
