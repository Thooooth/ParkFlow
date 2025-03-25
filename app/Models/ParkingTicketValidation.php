<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class ParkingTicketValidation extends Model
{
    protected $fillable = [
        'corporate_partner_id',
        'parking_session_id',
        'validated_by',
        'validation_date',
        'validated_minutes',
        'original_amount',
        'discounted_amount',
        'validation_code',
        'customer_reference',
        'notes',
    ];

    protected $casts = [
        'validation_date' => 'datetime',
        'validated_minutes' => 'integer',
        'original_amount' => 'float',
        'discounted_amount' => 'float',
    ];

    /**
     * Gera um novo código de validação único.
     */
    public static function generateValidationCode(): string
    {
        $code = strtoupper(Str::random(8));

        // Garante que o código seja único
        while (self::where('validation_code', $code)->exists()) {
            $code = strtoupper(Str::random(8));
        }

        return $code;
    }

    /**
     * Cria uma nova validação de ticket.
     */
    public static function createValidation(
        int $partnerId,
        int $sessionId,
        int $minutes,
        float $originalAmount,
        ?int $validatedBy = null,
        ?string $customerRef = null,
        ?string $notes = null
    ): self {
        $partner = CorporatePartner::findOrFail($partnerId);

        // Verifica se o parceiro está ativo
        if (!$partner->isActive()) {
            throw new \Exception('O parceiro corporativo não está ativo.');
        }

        // Verifica se o parceiro atingiu o limite mensal
        if ($partner->hasReachedMonthlyLimit()) {
            throw new \Exception('O parceiro atingiu o limite mensal de validações.');
        }

        // Aplica o desconto conforme configurado no parceiro
        $discountedAmount = $partner->applyDiscount($originalAmount);

        // Cria a validação
        return self::create([
            'corporate_partner_id' => $partnerId,
            'parking_session_id' => $sessionId,
            'validated_by' => $validatedBy,
            'validation_date' => now(),
            'validated_minutes' => $minutes,
            'original_amount' => $originalAmount,
            'discounted_amount' => $discountedAmount,
            'validation_code' => self::generateValidationCode(),
            'customer_reference' => $customerRef,
            'notes' => $notes,
        ]);
    }

    public function corporatePartner(): BelongsTo
    {
        return $this->belongsTo(CorporatePartner::class);
    }

    public function parkingSession(): BelongsTo
    {
        return $this->belongsTo(ParkingSession::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
