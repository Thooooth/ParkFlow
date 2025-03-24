<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'stripe_price_id',
        'price',
        'max_parking_lots',
        'max_users',
        'has_analytics',
        'has_api_access',
        'features',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_parking_lots' => 'integer',
        'max_users' => 'integer',
        'has_analytics' => 'boolean',
        'has_api_access' => 'boolean',
        'features' => 'array',
    ];

    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
