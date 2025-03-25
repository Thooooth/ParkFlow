<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class CorporatePartner extends Model
{
    protected $fillable = [
        'name',
        'cnpj',
        'address',
        'contact_person',
        'contact_email',
        'contact_phone',
        'status',
        'contract_start_date',
        'contract_end_date',
        'contract_terms',
        'discount_percentage',
        'monthly_ticket_limit',
        'api_key',
        'validation_method',
        'custom_settings',
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'discount_percentage' => 'float',
        'monthly_ticket_limit' => 'integer',
        'custom_settings' => 'array',
    ];

    /**
     * Ativa o parceiro corporativo.
     */
    public function activate(): void
    {
        $this->status = 'active';
        $this->save();
    }

    /**
     * Desativa o parceiro corporativo.
     */
    public function deactivate(): void
    {
        $this->status = 'inactive';
        $this->save();
    }

    /**
     * Gera uma nova chave de API.
     */
    public function generateApiKey(): string
    {
        $this->api_key = Str::random(64);
        $this->save();

        return $this->api_key;
    }

    /**
     * Aplica desconto ao valor com base na porcentagem configurada.
     */
    public function applyDiscount(float $amount): float
    {
        if ($this->discount_percentage <= 0) {
            return $amount;
        }

        $discount = $amount * ($this->discount_percentage / 100);
        return $amount - $discount;
    }

    /**
     * Verifica se o parceiro está ativo.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
            (!$this->contract_end_date || $this->contract_end_date->isFuture());
    }

    /**
     * Verifica se o parceiro atingiu o limite mensal.
     */
    public function hasReachedMonthlyLimit(): bool
    {
        if (!$this->monthly_ticket_limit) {
            return false;
        }

        $currentMonth = now()->startOfMonth();
        $count = $this->ticketValidations()
            ->where('validation_date', '>=', $currentMonth)
            ->count();

        return $count >= $this->monthly_ticket_limit;
    }

    /**
     * Obtém o total de validações do mês atual.
     */
    public function getCurrentMonthValidationsCount(): int
    {
        $currentMonth = now()->startOfMonth();
        return $this->ticketValidations()
            ->where('validation_date', '>=', $currentMonth)
            ->count();
    }

    public function ticketValidations(): HasMany
    {
        return $this->hasMany(ParkingTicketValidation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CorporateInvoice::class);
    }
}
