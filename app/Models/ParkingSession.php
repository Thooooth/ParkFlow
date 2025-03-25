<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Enums\StatusParkingSessionsEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ParkingSession extends Model
{
    protected $fillable = [
        'parking_lot_id',
        'vehicle_id',
        'user_id',
        'check_in',
        'check_out',
        'total_amount',
        'status',
        'reservation_id',
        'is_late_checkout',
        'late_fee',
        'parking_spot_id',
        'base_rate',
        'vehicle_size_surcharge',
        'special_spot_surcharge',
        'notes',
    ];

    protected $casts = [
        'status' => StatusParkingSessionsEnum::class,
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'total_amount' => 'float',
        'is_late_checkout' => 'boolean',
        'late_fee' => 'float',
        'vehicle_size_surcharge' => 'float',
        'special_spot_surcharge' => 'float',
    ];

    /**
     * Registra a saída do veículo e calcula o valor a ser cobrado.
     */
    public function checkOut(): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        $this->check_out = now();

        $parkingLot = $this->parkingLot;
        $reservation = $this->reservation;
        $vehicle = $this->vehicle;

        // Calcula o valor total com base no tipo de veículo
        $this->total_amount = $parkingLot->calculateParkingFee(
            $this->check_in,
            $this->check_out,
            $reservation,
            $vehicle
        );

        // Registra a tarifa base para este veículo
        $this->base_rate = $parkingLot->getAdjustedHourlyRate($vehicle);

        // Adiciona sobretaxa se o veículo for maior que o tamanho padrão
        if ($vehicle && $vehicle->size > 2) {
            $this->vehicle_size_surcharge = $this->base_rate * (($vehicle->size - 2) * 0.25);
        }

        // Adiciona sobretaxa para vagas especiais (elétrica ou adaptada)
        if (($vehicle->is_electric && $this->parkingSpot && $this->parkingSpot->has_charger) ||
            ($vehicle->is_disabled_adapted && $this->parkingSpot && $this->parkingSpot->is_disabled_friendly)) {
            $this->special_spot_surcharge = $this->base_rate * 0.15;
        }

        // Verifica se é um checkout tardio em relação à reserva
        if ($reservation && $reservation->end_time && $this->check_out > $reservation->end_time) {
            $this->is_late_checkout = true;
            $minutesLate = $this->check_out->diffInMinutes($reservation->end_time);
            $this->late_fee = $parkingLot->calculateLateFee($minutesLate, $vehicle);
            $this->total_amount += $this->late_fee;

            $this->addNote('late_checkout', 'Checkout tardio de ' . $minutesLate . ' minutos. Taxa adicional de R$' . number_format($this->late_fee, 2));
        }

        $this->status = 'completed';
        $this->save();

        // Libera as vagas ocupadas por este veículo
        if ($this->metadata) {
            $metadata = json_decode($this->metadata, true);

            // Se o veículo ocupava múltiplas vagas
            if (isset($metadata['all_spots']) && is_array($metadata['all_spots'])) {
                foreach ($metadata['all_spots'] as $spotId) {
                    $spot = ParkingSpot::find($spotId);
                    if ($spot) {
                        $spot->release();
                    }
                }
            } else if ($this->parking_spot_id) {
                // Caso tradicional - uma única vaga
                $this->parkingSpot->release();
            }
        } else if ($this->parking_spot_id) {
            // Fallback para o caso tradicional
            $this->parkingSpot->release();
        }

        // Atualiza o número de vagas disponíveis no estacionamento
        $parkingLot->available_spots = ParkingSpot::where('parking_lot_id', $parkingLot->id)
            ->where('status', 'available')
            ->count();
        $parkingLot->save();

        return true;
    }

    /**
     * Adiciona uma nota à sessão de estacionamento.
     */
    public function addNote(string $key, string $message): void
    {
        $notes = json_decode($this->notes ?? '{}', true);
        $notes[$key] = $message;
        $this->notes = json_encode($notes);
    }

    /**
     * Cria uma nova sessão de estacionamento.
     */
    public static function createSession(
        int $parkingLotId,
        int $vehicleId,
        int $userId,
        ?int $reservationId = null,
        ?int $parkingSpotId = null,
        array $vehicleDetails = []
    ): self {
        $parkingLot = ParkingLot::findOrFail($parkingLotId);

        // Recupera o veículo
        $vehicle = Vehicle::findOrFail($vehicleId);

        // Determina quantas vagas o veículo ocupa
        $spotsNeeded = $vehicle->spot_count;

        // Verifica se há vagas disponíveis suficientes
        if ($parkingLot->available_spots < $spotsNeeded) {
            throw new \Exception("Não há vagas disponíveis suficientes. Este veículo requer {$spotsNeeded} vagas.");
        }

        // Se não foi especificada uma vaga ou conjunto de vagas
        $spotIds = [];
        $primarySpotId = $parkingSpotId;

        if (!$primarySpotId) {
            // Determina se o veículo tem requisitos especiais
            $isDisabled = $vehicle->is_disabled_adapted ?? false;
            $isElectric = $vehicle->is_electric ?? false;

            // Se for um veículo normal que ocupa uma única vaga
            if ($spotsNeeded == 1) {
                // Busca uma vaga disponível adequada
                $spot = ParkingSpot::findAvailableSpot(
                    $parkingLotId,
                    $vehicle->getVehicleDetailsForSpotAllocation(),
                    $isDisabled,
                    $isElectric
                );

                if (!$spot) {
                    throw new \Exception('Não foi possível encontrar uma vaga adequada disponível.');
                }

                $primarySpotId = $spot->id;
                $spotIds[] = $primarySpotId;
            } else {
                // Para veículos que ocupam múltiplas vagas

                // Busca vagas adjacentes suficientes
                $adjacentSpots = self::findAdjacentAvailableSpots(
                    $parkingLotId,
                    $spotsNeeded,
                    $vehicle->getVehicleDetailsForSpotAllocation()
                );

                if (empty($adjacentSpots)) {
                    throw new \Exception("Não foi possível encontrar {$spotsNeeded} vagas adjacentes para este veículo.");
                }

                // A primeira vaga é a principal
                $primarySpotId = $adjacentSpots[0]->id;

                // Coleta todos os IDs de vagas
                foreach ($adjacentSpots as $spot) {
                    $spotIds[] = $spot->id;
                }
            }
        } else {
            // A vaga principal foi especificada
            $spotIds[] = $primarySpotId;

            // Para veículos que ocupam múltiplas vagas, precisamos encontrar vagas adjacentes
            if ($spotsNeeded > 1) {
                $primarySpot = ParkingSpot::findOrFail($primarySpotId);

                // Busca vagas adjacentes adicionais
                $additionalSpots = self::findAdjacentAvailableSpots(
                    $parkingLotId,
                    $spotsNeeded - 1, // Menos a vaga principal que já temos
                    $vehicle->getVehicleDetailsForSpotAllocation(),
                    $primarySpot
                );

                if (count($additionalSpots) < ($spotsNeeded - 1)) {
                    throw new \Exception("Não foi possível encontrar vagas adjacentes suficientes para este veículo.");
                }

                // Adiciona os IDs das vagas adicionais
                foreach ($additionalSpots as $spot) {
                    $spotIds[] = $spot->id;
                }
            }
        }

        // Reduza o número de vagas disponíveis no estacionamento
        for ($i = 0; $i < $spotsNeeded; $i++) {
            $parkingLot->checkInVehicle();
        }

        // Cria a sessão
        $session = self::create([
            'parking_lot_id' => $parkingLotId,
            'vehicle_id' => $vehicleId,
            'user_id' => $userId,
            'check_in' => now(),
            'status' => StatusParkingSessionsEnum::ACTIVE,
            'reservation_id' => $reservationId,
            'parking_spot_id' => $primarySpotId, // Vaga principal
        ]);

        // Registra as vagas ocupadas nos metadados
        $session->metadata = json_encode(['all_spots' => $spotIds]);
        $session->save();

        // Ocupa todas as vagas
        foreach ($spotIds as $spotId) {
            $spot = ParkingSpot::findOrFail($spotId);
            $spot->occupy($session->id);
        }

        // Se houver reserva, atualiza seu status
        if ($reservationId) {
            $reservation = ParkingReservation::findOrFail($reservationId);
            $reservation->checkIn($session->id);
        }

        // Registra informações sobre o veículo e as vagas nas notas
        if ($spotsNeeded > 1) {
            $session->addNote(
                'multiple_spots',
                sprintf(
                    'Veículo ocupa %d vagas. Vagas ocupadas: %s',
                    $spotsNeeded,
                    implode(', ', array_map(function($id) {
                        $spot = ParkingSpot::find($id);
                        return $spot ? $spot->full_location : "ID: {$id}";
                    }, $spotIds))
                )
            );
        }

        return $session;
    }

    /**
     * Encontra vagas adjacentes disponíveis para veículos grandes.
     */
    private static function findAdjacentAvailableSpots(
        int $parkingLotId,
        int $count,
        array $vehicleDetails,
        ?ParkingSpot $primarySpot = null
    ): array {
        // Se já temos uma vaga principal definida, encontramos vagas adjacentes a ela
        if ($primarySpot) {
            return self::findSpotsAdjacentToSpot($parkingLotId, $primarySpot, $count, $vehicleDetails);
        }

        // Determina o tipo de layout necessário com base nas características do veículo
        $layout = 'linear'; // padrão: vagas em sequência
        if (isset($vehicleDetails['vehicle']) && $vehicleDetails['vehicle'] instanceof Vehicle) {
            $layout = $vehicleDetails['vehicle']->getRequiredSpotLayout();
        }

        // Primeiro tentamos encontrar grupos de vagas já organizados para veículos grandes
        $preallocatedGroups = ParkingLot::findOrFail($parkingLotId)->spotGroups()
            ->where('spot_count', $count)
            ->where('status', 'available')
            ->where('layout', $layout)
            ->first();

        if ($preallocatedGroups) {
            return $preallocatedGroups->spots()->get()->all();
        }

        // Estratégia com base no layout necessário
        switch ($layout) {
            case 'linear':
                return self::findLinearSpots($parkingLotId, $count, $vehicleDetails);

            case 'side_by_side':
                return self::findSideBySideSpots($parkingLotId, $count, $vehicleDetails);

            case 'block':
                return self::findBlockSpots($parkingLotId, $count, $vehicleDetails);

            default:
                return self::findLinearSpots($parkingLotId, $count, $vehicleDetails);
        }
    }

    /**
     * Encontra vagas adjacentes a uma vaga principal.
     */
    private static function findSpotsAdjacentToSpot(
        int $parkingLotId,
        ParkingSpot $primarySpot,
        int $count,
        array $vehicleDetails
    ): array {
        // Busca as vagas que têm identificadores sequenciais
        // Esta é uma simplificação - em um caso real, precisaríamos de uma lógica mais robusta
        // para determinar quais vagas são realmente adjacentes com base no layout físico

        $zone = $primarySpot->zone;
        $floor = $primarySpot->floor;
        $identifier = $primarySpot->spot_identifier;

        // Tenta extrair parte numérica do identificador
        preg_match('/(\d+)/', $identifier, $matches);
        if (empty($matches)) {
            // Se não for possível extrair um número, usa uma abordagem diferente
            return self::findNearbySpots($parkingLotId, $primarySpot, $count);
        }

        $numericPart = (int) $matches[1];
        $prefixPart = str_replace($matches[1], '', $identifier);

        $adjacentSpots = [$primarySpot];

        // Busca vagas com identificadores sequenciais
        for ($i = 1; $i < $count; $i++) {
            $nextIdentifier = $prefixPart . ($numericPart + $i);

            $spot = ParkingSpot::where('parking_lot_id', $parkingLotId)
                ->where('zone', $zone)
                ->where('floor', $floor)
                ->where('spot_identifier', $nextIdentifier)
                ->where('status', 'available')
                ->first();

            if (!$spot) {
                // Se não encontrar as vagas adjacentes sequenciais, tenta uma abordagem diferente
                return self::findNearbySpots($parkingLotId, $primarySpot, $count);
            }

            $adjacentSpots[] = $spot;
        }

        return $adjacentSpots;
    }

    /**
     * Encontra vagas próximas umas das outras quando não é possível determinar adjacência por identificador.
     */
    private static function findNearbySpots(int $parkingLotId, ParkingSpot $primarySpot, int $count): array
    {
        $adjacentSpots = [$primarySpot];

        // Busca outras vagas disponíveis na mesma zona/andar
        $spots = ParkingSpot::where('parking_lot_id', $parkingLotId)
            ->where('zone', $primarySpot->zone)
            ->where('floor', $primarySpot->floor)
            ->where('status', 'available')
            ->where('id', '!=', $primarySpot->id)
            ->take($count - 1)
            ->get();

        foreach ($spots as $spot) {
            $adjacentSpots[] = $spot;
            if (count($adjacentSpots) >= $count) {
                break;
            }
        }

        if (count($adjacentSpots) < $count) {
            // Não foi possível encontrar vagas suficientes na mesma zona/andar
            return [];
        }

        return $adjacentSpots;
    }

    /**
     * Encontra vagas em sequência linear (para veículos compridos).
     */
    private static function findLinearSpots(int $parkingLotId, int $count, array $vehicleDetails): array
    {
        // Recupera preferências de zona e andar, se disponíveis
        $preferredZone = $vehicleDetails['preferred_zone'] ?? null;
        $preferredFloor = $vehicleDetails['preferred_floor'] ?? null;

        $query = ParkingSpot::where('parking_lot_id', $parkingLotId)
            ->where('status', 'available')
            ->orderBy('zone')
            ->orderBy('floor')
            ->orderBy('spot_identifier');

        // Aplica filtros de preferência, se existirem
        if ($preferredZone) {
            $query->where('zone', $preferredZone);
        }

        if ($preferredFloor) {
            $query->where('floor', $preferredFloor);
        }

        // Busca todas as vagas disponíveis que atendem aos critérios
        $availableSpots = $query->get();

        // Agrupa as vagas por zona e andar
        $spotsByZoneAndFloor = [];
        foreach ($availableSpots as $spot) {
            $key = $spot->zone . '-' . $spot->floor;
            if (!isset($spotsByZoneAndFloor[$key])) {
                $spotsByZoneAndFloor[$key] = [];
            }
            $spotsByZoneAndFloor[$key][] = $spot;
        }

        // Procura por sequências de vagas suficientes em cada grupo
        foreach ($spotsByZoneAndFloor as $spots) {
            // Ordena as vagas pelo identificador para encontrar sequências
            usort($spots, function ($a, $b) {
                // Extrai parte numérica dos identificadores
                preg_match('/(\d+)/', $a->spot_identifier, $matchesA);
                preg_match('/(\d+)/', $b->spot_identifier, $matchesB);

                $numA = isset($matchesA[1]) ? (int)$matchesA[1] : 0;
                $numB = isset($matchesB[1]) ? (int)$matchesB[1] : 0;

                return $numA - $numB;
            });

            // Verifica sequências contíguas
            for ($i = 0; $i <= count($spots) - $count; $i++) {
                $sequence = array_slice($spots, $i, $count);

                // Verifica se os identificadores são sequenciais
                $isSequential = true;
                for ($j = 1; $j < count($sequence); $j++) {
                    preg_match('/(\d+)/', $sequence[$j-1]->spot_identifier, $matchesPrev);
                    preg_match('/(\d+)/', $sequence[$j]->spot_identifier, $matchesCurrent);

                    if (empty($matchesPrev) || empty($matchesCurrent)) {
                        $isSequential = false;
                        break;
                    }

                    $numPrev = (int)$matchesPrev[1];
                    $numCurrent = (int)$matchesCurrent[1];

                    if ($numCurrent !== $numPrev + 1) {
                        $isSequential = false;
                        break;
                    }
                }

                if ($isSequential) {
                    return $sequence;
                }
            }
        }

        return [];
    }

    /**
     * Encontra vagas lado a lado (para veículos largos).
     */
    private static function findSideBySideSpots(int $parkingLotId, int $count, array $vehicleDetails): array
    {
        // Em um sistema real, precisaríamos conhecer o layout físico do estacionamento
        // Esta é uma simplificação que assume que as vagas estão organizadas em linhas
        // com numeração sequencial, onde vagas lado a lado podem ter padrões como A1/B1, A2/B2.

        $preferredZone = $vehicleDetails['preferred_zone'] ?? null;
        $preferredFloor = $vehicleDetails['preferred_floor'] ?? null;

        $query = ParkingSpot::where('parking_lot_id', $parkingLotId)
            ->where('status', 'available');

        if ($preferredZone) {
            $query->where('zone', $preferredZone);
        }

        if ($preferredFloor) {
            $query->where('floor', $preferredFloor);
        }

        $spots = $query->get();

        // Agrupa as vagas pelo número sem a letra (ex: A1, B1, C1 ficam no grupo "1")
        $spotsByNumber = [];
        foreach ($spots as $spot) {
            preg_match('/([a-zA-Z]+)(\d+)/', $spot->spot_identifier, $matches);
            if (count($matches) >= 3) {
                $number = $matches[2];
                if (!isset($spotsByNumber[$number])) {
                    $spotsByNumber[$number] = [];
                }
                $spotsByNumber[$number][] = $spot;
            }
        }

        // Procura por grupos que tenham vagas suficientes lado a lado
        foreach ($spotsByNumber as $number => $groupSpots) {
            if (count($groupSpots) >= $count) {
                // Ordena as vagas por letra para garantir que sejam adjacentes
                usort($groupSpots, function ($a, $b) {
                    preg_match('/([a-zA-Z]+)/', $a->spot_identifier, $matchesA);
                    preg_match('/([a-zA-Z]+)/', $b->spot_identifier, $matchesB);

                    $letterA = $matchesA[1] ?? '';
                    $letterB = $matchesB[1] ?? '';

                    return strcmp($letterA, $letterB);
                });

                return array_slice($groupSpots, 0, $count);
            }
        }

        // Se não encontrar vagas lado a lado, tenta buscar vagas próximas
        if (count($spots) >= $count) {
            return array_slice($spots->all(), 0, $count);
        }

        return [];
    }

    /**
     * Encontra vagas em formato de bloco (para veículos muito grandes).
     */
    private static function findBlockSpots(int $parkingLotId, int $count, array $vehicleDetails): array
    {
        // Esta função seria idealmente implementada com conhecimento do layout real do estacionamento
        // e da posição física das vagas. Como simplificação, tentamos encontrar vagas próximas.

        $preferredZone = $vehicleDetails['preferred_zone'] ?? null;
        $preferredFloor = $vehicleDetails['preferred_floor'] ?? null;

        $query = ParkingSpot::where('parking_lot_id', $parkingLotId)
            ->where('status', 'available');

        if ($preferredZone) {
            $query->where('zone', $preferredZone);
        }

        if ($preferredFloor) {
            $query->where('floor', $preferredFloor);
        }

        $spots = $query->take($count)->get();

        if (count($spots) < $count) {
            return [];
        }

        return $spots->all();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}