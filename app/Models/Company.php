<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Enums\StatusCompanyEnum;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;
use Carbon\Carbon;
final class Company extends Model
{
    use Billable;

    protected $fillable = [
        'name',
        'cnpj',
        'email',
        'phone',
        'address',
        'subscription_status',
        'trial_ends_at',
    ];

    protected $dates = [
        'trial_ends_at',
        'subscription_ends_at',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function parkingLots()
    {
        return $this->hasMany(ParkingLot::class);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function isSubscribed()
    {
        return $this->subscription_status === StatusCompanyEnum::ACTIVE->value;
    }

    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($company): void {
            $company->subscription_status = StatusCompanyEnum::ACTIVE->value;
            $company->trial_ends_at = Carbon::now()->addDays(15);
        });
    }
}