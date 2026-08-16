<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resort extends Model
{
    protected $guarded = [];

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function getLocationAttribute(): string
    {
        return collect([$this->city, $this->state ?: $this->country])->filter()->implode(', ');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
