<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'discount_percent',
        'max_uses',
        'uses',
        'is_active',
    ];

    protected $casts = [
        'discount_percent' => 'integer',
        'max_uses' => 'integer',
        'uses' => 'integer',
        'is_active' => 'boolean',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }
}
