<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Enums\RoleUserEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($user): void {
            $user->role ??= RoleUserEnum::OPERATOR;
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isAdmin()
    {
        return RoleUserEnum::ADMIN === $this->role;
    }

    public function isManager()
    {
        return RoleUserEnum::MANAGER === $this->role;
    }

    public function isOperator()
    {
        return RoleUserEnum::OPERATOR === $this->role;
    }

    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
