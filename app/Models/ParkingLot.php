<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ParkingLot extends Model
{
    protected $fillable = [
        'name',
        'address',
        'total_spots',
        'monthly_spots',
        'available_monthly_spots',
        'available_spots',
        'hourly_rate',
        'additional_hour_rate',
        'daily_rate',
        'daily_period',
        'monthly_rate',
        'opening_time',
        'closing_time',
        'company_id',
    ];

    protected $casts = [
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
        'daily_period' => 'integer',
    ];

    /**
     * Calcula o total de vagas disponíveis para o público geral.
     * As vagas não ocupadas por mensalistas são adicionadas às vagas regulares.
     * Considera também as reservas ativas para o período especificado.
     */
    public function getTotalAvailableSpotsAttribute(): int
    {
        $regularSpots = $this->total_spots;
        $unusedMonthlySpots = $this->available_monthly_spots;

        return $regularSpots + $unusedMonthlySpots;
    }

    /**
     * Calcula o número de vagas disponíveis considerando também as reservas ativas.
     *
     * @param \DateTimeInterface|null $targetDateTime Data e hora alvo para verificação
     * @return int Número de vagas disponíveis no momento especificado
     */
    public function getAvailableSpotsAt(\DateTimeInterface $targetDateTime = null): int
    {
        $targetDateTime = $targetDateTime ?? now();

        // Número base de vagas disponíveis
        $availableSpots = $this->available_spots;

        // Conta reservas ativas que se sobrepõem ao horário alvo
        $activeReservations = $this->reservations()
            ->where('status', 'confirmed')
            ->where('start_time', '<=', $targetDateTime)
            ->where('end_time', '>=', $targetDateTime)
            ->whereNull('check_in_time') // Apenas reservas que ainda não deram check-in
            ->count();

        return max(0, $availableSpots - $activeReservations);
    }

    /**
     * Verifica se há vagas disponíveis para um período específico, considerando reservas.
     *
     * @param \DateTimeInterface $startTime Hora de início
     * @param \DateTimeInterface $endTime Hora de término
     * @return bool True se houver vagas disponíveis durante todo o período
     */
    public function hasAvailableSpotsForPeriod(\DateTimeInterface $startTime, \DateTimeInterface $endTime): bool
    {
        // Verifica se há vagas disponíveis no momento atual
        if ($this->available_spots <= 0) {
            return false;
        }

        // Conta reservas ativas que se sobrepõem ao período solicitado
        $conflictingReservations = $this->reservations()
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($query) use ($startTime, $endTime) {
                        $query->where('start_time', '<=', $startTime)
                              ->where('end_time', '>=', $endTime);
                    });
            })
            ->whereNull('check_in_time')
            ->count();

        // Verifica se o número de reservas conflitantes não excede as vagas disponíveis
        return $conflictingReservations < $this->available_spots;
    }

    /**
     * Atualiza as vagas disponíveis para mensalistas baseado na contagem atual.
     */
    public function updateAvailableMonthlySpots(): void
    {
        $usedMonthlySpots = $this->monthlySubscribers()->where('is_active', true)->count();
        $this->available_monthly_spots = $this->monthly_spots - $usedMonthlySpots;
        $this->save();
    }

    public function parkingSessions(): HasMany
    {
        return $this->hasMany(ParkingSession::class);
    }

    public function monthlySubscribers(): HasMany
    {
        return $this->hasMany(MonthlySubscriber::class);
    }

    public function valetRequests(): HasMany
    {
        return $this->hasMany(ValetRequest::class);
    }

    public function valetOperators(): HasMany
    {
        return $this->hasMany(ValetOperator::class);
    }

    public function vehicleHandovers(): HasMany
    {
        return $this->hasMany(VehicleHandover::class);
    }

    public function vehicleIncidents(): HasMany
    {
        return $this->hasMany(VehicleIncident::class);
    }

    public function chargingStations(): HasMany
    {
        return $this->hasMany(ChargingStation::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ParkingReservation::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Registra a entrada de um veículo e atualiza o contador de vagas disponíveis.
     */
    public function checkInVehicle(): bool
    {
        if ($this->available_spots > 0) {
            $this->available_spots -= 1;
            $this->save();
            return true;
        }

        return false; // Não há vagas disponíveis
    }

    /**
     * Registra a saída de um veículo e atualiza o contador de vagas disponíveis.
     */
    public function checkOutVehicle(): void
    {
        $this->available_spots += 1;
        $this->save();
    }

    /**
     * Calcula o valor a ser cobrado com base no tempo de permanência,
     * considerando também as reservas e eventuais atrasos.
     *
     * @param \DateTimeInterface $checkIn Data e hora de entrada
     * @param \DateTimeInterface $checkOut Data e hora de saída
     * @param ParkingReservation|null $reservation Reserva associada, se houver
     * @return float Valor total a ser cobrado
     */
    public function calculateParkingFee(\DateTimeInterface $checkIn, \DateTimeInterface $checkOut, ?ParkingReservation $reservation = null): float
    {
        // Se não houver reserva, usa a lógica padrão
        if (!$reservation) {
            return $this->calculateStandardParkingFee($checkIn, $checkOut);
        }

        // Se houver reserva, verifica se houve atraso na saída
        if ($checkOut > $reservation->end_time) {
            // Calcula a taxa da reserva (já paga ou a ser paga)
            $reservationFee = $this->calculateStandardParkingFee($reservation->start_time, $reservation->end_time);

            // Calcula a taxa adicional pelo tempo excedido
            $lateFee = $this->calculateLateFee($reservation->end_time, $checkOut);

            return $reservationFee + $lateFee;
        }

        // Se saiu antes ou no horário previsto, cobra apenas o valor da reserva
        return $this->calculateStandardParkingFee($reservation->start_time, $reservation->end_time);
    }

    /**
     * Calcula o valor padrão a ser cobrado com base no tempo de permanência.
     *
     * @param \DateTimeInterface $checkIn Data e hora de entrada
     * @param \DateTimeInterface $checkOut Data e hora de saída
     * @return float Valor total a ser cobrado
     */
    public function calculateStandardParkingFee(\DateTimeInterface $checkIn, \DateTimeInterface $checkOut): float
    {
        // Calcula a duração em horas (arredondando para cima)
        $duration = ceil($checkOut->getTimestamp() - $checkIn->getTimestamp()) / 3600;

        // Calcula o número de períodos diários completos
        $dailyPeriod = $this->daily_period ?: 24; // Se não definido, assume 24 horas
        $fullDays = floor($duration / $dailyPeriod);

        // Calcula as horas restantes após os períodos diários completos
        $remainingHours = $duration - ($fullDays * $dailyPeriod);

        // Valor base para os períodos diários completos
        $fee = $fullDays * $this->daily_rate;

        // Adiciona o valor para as horas restantes
        if ($remainingHours > 0) {
            // Primeira hora
            $remainingFee = $this->hourly_rate;

            // Horas adicionais
            if ($remainingHours > 1) {
                $remainingFee += min($this->additional_hour_rate * ($remainingHours - 1),
                                    $this->daily_rate - $this->hourly_rate);
            }

            // Limite o valor das horas restantes ao valor da diária
            $remainingFee = min($remainingFee, $this->daily_rate);

            $fee += $remainingFee;
        }

        return $fee;
    }

    /**
     * Calcula o valor adicional por atraso na saída após o horário reservado.
     *
     * @param \DateTimeInterface $scheduledEnd Horário previsto de saída
     * @param \DateTimeInterface $actualEnd Horário real de saída
     * @return float Valor adicional a ser cobrado
     */
    private function calculateLateFee(\DateTimeInterface $scheduledEnd, \DateTimeInterface $actualEnd): float
    {
        // Calcula a duração do atraso em horas (arredondando para cima)
        $overtime = ceil($actualEnd->getTimestamp() - $scheduledEnd->getTimestamp()) / 3600;

        // Aplica uma taxa adicional para atrasos (primeira hora)
        $lateFee = $this->hourly_rate;

        // Horas adicionais de atraso
        if ($overtime > 1) {
            $lateFee += $this->additional_hour_rate * ($overtime - 1);
        }

        // Para atrasos muito longos, considera períodos diários
        if ($overtime >= $this->daily_period) {
            $dailyPeriod = $this->daily_period ?: 24;
            $fullDays = floor($overtime / $dailyPeriod);
            $remainingHours = $overtime - ($fullDays * $dailyPeriod);

            $lateFee = $fullDays * $this->daily_rate;

            if ($remainingHours > 0) {
                // Primeira hora do período restante
                $remainingFee = $this->hourly_rate;

                // Horas adicionais
                if ($remainingHours > 1) {
                    $remainingFee += min($this->additional_hour_rate * ($remainingHours - 1),
                                       $this->daily_rate - $this->hourly_rate);
                }

                // Limite o valor das horas restantes ao valor da diária
                $remainingFee = min($remainingFee, $this->daily_rate);

                $lateFee += $remainingFee;
            }
        }

        return $lateFee;
    }

    /**
     * Retorna o relacionamento com as vagas de estacionamento.
     */
    public function parkingSpots(): HasMany
    {
        return $this->hasMany(ParkingSpot::class);
    }
}
