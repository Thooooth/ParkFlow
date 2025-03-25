<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

final class ParkingReservation extends Model
{
    protected $fillable = [
        'parking_lot_id',
        'user_id',
        'start_time',
        'end_time',
        'vehicle_plate',
        'vehicle_model',
        'vehicle_color',
        'status',
        'confirmation_code',
        'qr_code',
        'reservation_fee',
        'estimated_total',
        'discount_amount',
        'discount_code',
        'is_paid',
        'payment_id',
        'payment_method',
        'check_in_time',
        'check_out_time',
        'parking_session_id',
        'reminder_sent',
        'reminder_sent_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'reservation_fee' => 'float',
        'estimated_total' => 'float',
        'discount_amount' => 'float',
        'is_paid' => 'boolean',
        'reminder_sent' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Gera um código de confirmação único.
     */
    public static function generateConfirmationCode(): string
    {
        $code = strtoupper(Str::random(8));

        // Garante que o código seja único
        while (self::where('confirmation_code', $code)->exists()) {
            $code = strtoupper(Str::random(8));
        }

        return $code;
    }

    /**
     * Cria uma nova reserva.
     */
    public static function createReservation(
        int $parkingLotId,
        int $userId,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        array $vehicleDetails = [],
        ?string $discountCode = null,
        ?array $metadata = null
    ): self {
        $parkingLot = ParkingLot::findOrFail($parkingLotId);

        // Calcula o período da reserva em horas
        $durationHours = $startTime->diff($endTime)->h + ($startTime->diff($endTime)->days * 24);

        // Calcula as taxas
        $reservationFee = 10.00; // Taxa fixa de reserva

        // Calcula o valor estimado total
        $estimatedTotal = $parkingLot->calculateParkingFee($startTime, $endTime) + $reservationFee;

        // Aplica desconto se houver código de desconto
        $discountAmount = 0;
        if ($discountCode) {
            // Lógica para aplicar desconto (simplificada)
            $discountAmount = $estimatedTotal * 0.1; // 10% de desconto
        }

        // Cria a reserva
        $reservation = self::create([
            'parking_lot_id' => $parkingLotId,
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'vehicle_plate' => $vehicleDetails['plate'] ?? null,
            'vehicle_model' => $vehicleDetails['model'] ?? null,
            'vehicle_color' => $vehicleDetails['color'] ?? null,
            'status' => 'pending',
            'confirmation_code' => self::generateConfirmationCode(),
            'reservation_fee' => $reservationFee,
            'estimated_total' => $estimatedTotal - $discountAmount,
            'discount_amount' => $discountAmount,
            'discount_code' => $discountCode,
            'metadata' => $metadata,
        ]);

        // Gera o QR Code
        $reservation->generateQrCode();

        return $reservation;
    }

    /**
     * Gera um QR Code para a reserva.
     */
    public function generateQrCode(): void
    {
        $data = [
            'id' => $this->id,
            'code' => $this->confirmation_code,
            'user' => $this->user_id,
            'parking_lot' => $this->parking_lot_id,
            'start' => $this->start_time->format('Y-m-d H:i:s'),
            'end' => $this->end_time->format('Y-m-d H:i:s'),
        ];

        $jsonData = json_encode($data);

        // Gera o QR Code
        $qrCode = QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->generate($jsonData);

        // Nome do arquivo
        $filename = 'qrcodes/reservation_' . $this->id . '_' . time() . '.png';

        // Salva o QR Code
        Storage::disk('public')->put($filename, $qrCode);

        // Atualiza o caminho do QR Code
        $this->qr_code = $filename;
        $this->save();
    }

    /**
     * Confirma a reserva.
     */
    public function confirm(): void
    {
        $this->status = 'confirmed';
        $this->save();
    }

    /**
     * Cancela a reserva.
     */
    public function cancel(?string $reason = null): void
    {
        $this->status = 'cancelled';

        if ($reason) {
            $this->notes = $this->notes
                ? $this->notes . "\n" . "Cancelada em " . now()->format('d/m/Y H:i') . ": " . $reason
                : "Cancelada em " . now()->format('d/m/Y H:i') . ": " . $reason;
        }

        $this->save();
    }

    /**
     * Marca a reserva como paga.
     */
    public function markAsPaid(string $paymentId, string $paymentMethod): void
    {
        $this->is_paid = true;
        $this->payment_id = $paymentId;
        $this->payment_method = $paymentMethod;
        $this->save();

        // Se estiver paga, confirma a reserva automaticamente
        if ($this->status === 'pending') {
            $this->confirm();
        }
    }

    /**
     * Registra o check-in da reserva.
     */
    public function checkIn(int $parkingSessionId): void
    {
        $this->check_in_time = now();
        $this->parking_session_id = $parkingSessionId;
        $this->status = 'completed';
        $this->save();
    }

    /**
     * Registra o check-out da reserva.
     */
    public function checkOut(): void
    {
        $this->check_out_time = now();
        $this->save();
    }

    /**
     * Envia lembretes para o cliente.
     */
    public function sendReminder(): void
    {
        // Lógica para enviar lembrete (implementação simplificada)
        $this->reminder_sent = true;
        $this->reminder_sent_at = now();
        $this->save();
    }

    /**
     * Marca a reserva como no-show.
     */
    public function markAsNoShow(): void
    {
        $this->status = 'no_show';
        $this->save();
    }

    /**
     * Verifica se a reserva está dentro da janela de cancelamento gratuito.
     */
    public function isWithinFreeCancellationWindow(): bool
    {
        // Verifica se a reserva ainda pode ser cancelada sem custo (24h antes)
        $cancellationDeadline = $this->start_time->copy()->subHours(24);
        return now()->lt($cancellationDeadline);
    }

    public function parkingLot(): BelongsTo
    {
        return $this->belongsTo(ParkingLot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parkingSession(): BelongsTo
    {
        return $this->belongsTo(ParkingSession::class);
    }
}
