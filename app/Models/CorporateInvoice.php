<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class CorporateInvoice extends Model
{
    protected $fillable = [
        'corporate_partner_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'total_amount',
        'total_validations',
        'total_minutes_validated',
        'status',
        'payment_date',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'total_amount' => 'float',
        'total_validations' => 'integer',
        'total_minutes_validated' => 'integer',
    ];

    /**
     * Gera um número de fatura único.
     */
    public static function generateInvoiceNumber(int $partnerId): string
    {
        $partner = CorporatePartner::findOrFail($partnerId);
        $prefix = strtoupper(substr(str_replace(' ', '', $partner->name), 0, 3));
        $date = now()->format('Ym');
        $random = strtoupper(Str::random(4));

        $invoiceNumber = "{$prefix}{$date}-{$random}";

        // Garante que o número da fatura seja único
        while (self::where('invoice_number', $invoiceNumber)->exists()) {
            $random = strtoupper(Str::random(4));
            $invoiceNumber = "{$prefix}{$date}-{$random}";
        }

        return $invoiceNumber;
    }

    /**
     * Gera fatura para um parceiro corporativo para um período específico.
     */
    public static function generateInvoice(int $partnerId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): self
    {
        $partner = CorporatePartner::findOrFail($partnerId);

        // Obtém todas as validações do período
        $validations = ParkingTicketValidation::where('corporate_partner_id', $partnerId)
            ->whereBetween('validation_date', [$startDate, $endDate])
            ->whereNotExists(function ($query) {
                $query->select(1)
                    ->from('corporate_invoice_items')
                    ->whereColumn('corporate_invoice_items.parking_ticket_validation_id', 'parking_ticket_validations.id');
            })
            ->get();

        if ($validations->isEmpty()) {
            throw new \Exception('Não há validações para faturar neste período.');
        }

        // Calcula os totais
        $totalAmount = $validations->sum('discounted_amount');
        $totalValidations = $validations->count();
        $totalMinutes = $validations->sum('validated_minutes');

        // Cria a fatura
        $invoice = self::create([
            'corporate_partner_id' => $partnerId,
            'invoice_number' => self::generateInvoiceNumber($partnerId),
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'total_amount' => $totalAmount,
            'total_validations' => $totalValidations,
            'total_minutes_validated' => $totalMinutes,
            'status' => 'pending',
        ]);

        // Adiciona os itens da fatura
        foreach ($validations as $validation) {
            CorporateInvoiceItem::create([
                'corporate_invoice_id' => $invoice->id,
                'parking_ticket_validation_id' => $validation->id,
                'amount' => $validation->discounted_amount,
                'description' => 'Validação de estacionamento - ' . $validation->validation_code,
            ]);
        }

        return $invoice;
    }

    /**
     * Marca a fatura como paga.
     */
    public function markAsPaid(string $paymentMethod, string $reference = null, \DateTimeInterface $paymentDate = null): void
    {
        $this->status = 'paid';
        $this->payment_method = $paymentMethod;
        $this->payment_reference = $reference;
        $this->payment_date = $paymentDate ?? now();
        $this->save();
    }

    /**
     * Marca a fatura como vencida.
     */
    public function markAsOverdue(): void
    {
        if ($this->status === 'pending' && $this->due_date->isPast()) {
            $this->status = 'overdue';
            $this->save();
        }
    }

    /**
     * Cancela a fatura.
     */
    public function cancel(string $reason): void
    {
        $this->status = 'canceled';
        $this->notes = $this->notes
            ? $this->notes . "\n" . "Cancelada em " . now()->format('d/m/Y') . ": " . $reason
            : "Cancelada em " . now()->format('d/m/Y') . ": " . $reason;
        $this->save();
    }

    public function corporatePartner(): BelongsTo
    {
        return $this->belongsTo(CorporatePartner::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CorporateInvoiceItem::class);
    }
}
