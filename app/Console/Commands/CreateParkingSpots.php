<?php

namespace App\Console\Commands;

use App\Models\ParkingLot;
use App\Models\ParkingSpot;
use Illuminate\Console\Command;

class CreateParkingSpots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parking:create-spots
                            {parking_lot_id : ID do estacionamento}
                            {--count=50 : Número de vagas a serem criadas}
                            {--floors=1 : Número de andares}
                            {--zones=1 : Número de zonas por andar}
                            {--spots-per-zone=10 : Número de vagas por zona}
                            {--disabled-spots=2 : Número de vagas para PCD}
                            {--electric-spots=2 : Número de vagas para veículos elétricos}
                            {--large-spots=5 : Número de vagas grandes}
                            {--format=numeric : Formato do identificador (numeric, alpha, alphanumeric)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria vagas para um estacionamento específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $parkingLotId = $this->argument('parking_lot_id');
        $parkingLot = ParkingLot::find($parkingLotId);

        if (!$parkingLot) {
            $this->error("Estacionamento com ID {$parkingLotId} não encontrado.");
            return 1;
        }

        // Obtém os parâmetros
        $totalSpots = $this->option('count');
        $floors = $this->option('floors');
        $zonesPerFloor = $this->option('zones');
        $spotsPerZone = $this->option('spots-per-zone');
        $disabledSpots = $this->option('disabled-spots');
        $electricSpots = $this->option('electric-spots');
        $largeSpots = $this->option('large-spots');
        $format = $this->option('format');

        // Verifica se o número total de vagas é compatível
        $calculatedTotal = $floors * $zonesPerFloor * $spotsPerZone;
        if ($calculatedTotal < $totalSpots) {
            $this->info("Ajustando número total de vagas para {$calculatedTotal} com base nas configurações informadas.");
            $totalSpots = $calculatedTotal;
        }

        // Contadores
        $spotCount = 0;
        $disabledCount = 0;
        $electricCount = 0;
        $largeCount = 0;

        $progress = $this->output->createProgressBar($totalSpots);
        $progress->start();

        $this->info("Criando {$totalSpots} vagas para o estacionamento \"{$parkingLot->name}\"");

        // Cria as vagas
        for ($floor = 1; $floor <= $floors; $floor++) {
            $floorName = $floor;

            // Para múltiplos andares, usa códigos como T, 1, 2, etc.
            if ($floors > 1) {
                $floorName = ($floor == 1) ? 'T' : ($floor - 1);
            }

            for ($zone = 1; $zone <= $zonesPerFloor; $zone++) {
                $zoneName = $this->getZoneIdentifier($zone, $zonesPerFloor);

                for ($spot = 1; $spot <= $spotsPerZone; $spot++) {
                    // Verifica se já atingiu o número total de vagas
                    if ($spotCount >= $totalSpots) {
                        break 3; // Sai de todos os loops
                    }

                    $spotIdentifier = $this->getSpotIdentifier($spot, $format);

                    // Decide se esta vaga é especial
                    $isDisabled = $disabledCount < $disabledSpots;
                    $isElectric = !$isDisabled && $electricCount < $electricSpots;
                    $isLarge = $largeCount < $largeSpots;

                    $size = $isLarge ? 2 : 1; // Tamanho 2 para vagas grandes

                    ParkingSpot::create([
                        'parking_lot_id' => $parkingLotId,
                        'spot_identifier' => $spotIdentifier,
                        'zone' => $zoneName,
                        'floor' => $floorName,
                        'is_reserved_for_disabled' => $isDisabled,
                        'is_reserved_for_electric' => $isElectric,
                        'size' => $size,
                        'status' => 'available',
                    ]);

                    // Atualiza contadores
                    $spotCount++;
                    if ($isDisabled) $disabledCount++;
                    if ($isElectric) $electricCount++;
                    if ($isLarge) $largeCount++;

                    $progress->advance();
                }
            }
        }

        $progress->finish();
        $this->newLine(2);

        $this->info("Foram criadas {$spotCount} vagas para o estacionamento \"{$parkingLot->name}\"");
        $this->info("- {$disabledCount} vagas para PCD");
        $this->info("- {$electricCount} vagas para veículos elétricos");
        $this->info("- {$largeCount} vagas grandes");

        return 0;
    }

    /**
     * Gera um identificador para a zona.
     */
    private function getZoneIdentifier(int $zone, int $totalZones): string
    {
        // Para poucas zonas, usa letras (A, B, C)
        if ($totalZones <= 26) {
            return chr(64 + $zone);
        }

        // Para muitas zonas, usa números
        return (string) $zone;
    }

    /**
     * Gera um identificador para a vaga.
     */
    private function getSpotIdentifier(int $spot, string $format): string
    {
        switch ($format) {
            case 'alpha':
                // Converte para letras (A, B, C...)
                return $this->numberToAlpha($spot);

            case 'alphanumeric':
                // Combina letras e números (A1, A2...)
                $letter = chr(64 + ceil($spot / 100));
                $number = $spot % 100;
                return $letter . $number;

            case 'numeric':
            default:
                // Apenas números, com zeros à esquerda para menos de 100
                return $spot < 100 ? sprintf("%02d", $spot) : (string) $spot;
        }
    }

    /**
     * Converte um número para representação alfabética.
     */
    private function numberToAlpha(int $num): string
    {
        $alphabet = range('A', 'Z');

        if ($num <= 26) {
            return $alphabet[$num - 1];
        }

        $dividend = $num;
        $result = '';

        while ($dividend > 0) {
            $modulo = ($dividend - 1) % 26;
            $result = $alphabet[$modulo] . $result;
            $dividend = floor(($dividend - $modulo) / 26);
        }

        return $result;
    }
}
