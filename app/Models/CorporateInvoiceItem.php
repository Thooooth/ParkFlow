<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CorporateInvoiceItem extends Model
{
    protected $fillable = [
        'corporate_invoice_id',
        'parking_ticket_validation_id',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CorporateInvoice::class, 'corporate_invoice_id');
    }

    public function ticketValidation(): BelongsTo
    {
        return $this->belongsTo(ParkingTicketValidation::class, 'parking_ticket_validation_id');
    }
}
