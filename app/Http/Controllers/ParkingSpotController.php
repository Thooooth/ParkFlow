<?php

namespace App\Http\Controllers;

use App\Models\ParkingLot;
use App\Models\ParkingSpot;
use App\Models\ParkingSession;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ParkingSpotController extends Controller
{
    /**
     * Exibe a lista de vagas para um estacionamento.
     */
    public function index(Request $request)
    {
        $parkingLot = ParkingLot::findOrFail($request->parking_lot_id);

        $status = $request->status ?? 'all';
        $floor = $request->floor ?? null;
        $zone = $request->zone ?? null;

        $query = $parkingLot->parkingSpots();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($floor) {
            $query->where('floor', $floor);
        }

        if ($zone) {
            $query->where('zone', $zone);
        }

        $spots = $query->orderBy('floor')
            ->orderBy('zone')
            ->orderBy('spot_identifier')
            ->paginate(30);

        $stats = [
            'total' => $parkingLot->parkingSpots()->count(),
            'available' => $parkingLot->parkingSpots()->where('status', 'available')->count(),
            'occupied' => $parkingLot->parkingSpots()->where('status', 'occupied')->count(),
            'reserved' => $parkingLot->parkingSpots()->where('status', 'reserved')->count(),
            'maintenance' => $parkingLot->parkingSpots()->where('status', 'maintenance')->count(),
        ];

        $floors = $parkingLot->parkingSpots()->distinct('floor')->pluck('floor');
        $zones = $parkingLot->parkingSpots()->distinct('zone')->pluck('zone');

        return view('parking-spots.index', compact(
            'parkingLot',
            'spots',
            'stats',
            'floors',
            'zones',
            'status',
            'floor',
            'zone'
        ));
    }

    /**
     * API para buscar vagas em tempo real com suas ocupações.
     */
    public function getSpotStatus(Request $request)
    {
        $parkingLot = ParkingLot::findOrFail($request->parking_lot_id);

        $floor = $request->floor ?? null;
        $zone = $request->zone ?? null;

        $query = $parkingLot->parkingSpots();

        if ($floor) {
            $query->where('floor', $floor);
        }

        if ($zone) {
            $query->where('zone', $zone);
        }

        $spots = $query->with(['currentSession.vehicle', 'currentReservation'])
            ->get()
            ->map(function($spot) {
                $sessionInfo = null;

                if ($spot->status === 'occupied' && $spot->currentSession) {
                    $vehicle = $spot->currentSession->vehicle;
                    $sessionInfo = [
                        'time' => $spot->occupied_since->diffForHumans(),
                        'plate' => $vehicle ? $vehicle->plate : 'N/A',
                        'model' => $vehicle ? $vehicle->model : 'N/A',
                        'color' => $vehicle ? $vehicle->color : 'N/A',
                    ];
                }

                return [
                    'id' => $spot->id,
                    'identifier' => $spot->spot_identifier,
                    'zone' => $spot->zone,
                    'floor' => $spot->floor,
                    'status' => $spot->status,
                    'is_disabled' => $spot->is_reserved_for_disabled,
                    'is_electric' => $spot->is_reserved_for_electric,
                    'session' => $sessionInfo,
                    'full_location' => $spot->full_location,
                ];
            });

        return response()->json([
            'success' => true,
            'spots' => $spots
        ]);
    }

    /**
     * Exibe o formulário para criar uma nova vaga.
     */
    public function create(Request $request)
    {
        $parkingLot = ParkingLot::findOrFail($request->parking_lot_id);

        $floors = $parkingLot->parkingSpots()->distinct('floor')->pluck('floor');
        $zones = $parkingLot->parkingSpots()->distinct('zone')->pluck('zone');

        return view('parking-spots.create', compact('parkingLot', 'floors', 'zones'));
    }

    /**
     * Armazena uma nova vaga.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parking_lot_id' => 'required|exists:parking_lots,id',
            'spot_identifier' => 'required|string|max:20',
            'zone' => 'nullable|string|max:20',
            'floor' => 'nullable|string|max:10',
            'is_reserved_for_disabled' => 'boolean',
            'is_reserved_for_electric' => 'boolean',
            'size' => 'integer|in:1,2,3',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verifica se já existe uma vaga com o mesmo identificador neste estacionamento
        $exists = ParkingSpot::where('parking_lot_id', $request->parking_lot_id)
            ->where('spot_identifier', $request->spot_identifier)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'spot_identifier' => 'Já existe uma vaga com este identificador neste estacionamento.'
            ])->withInput();
        }

        ParkingSpot::create($request->all());

        return redirect()->route('parking-spots.index', ['parking_lot_id' => $request->parking_lot_id])
            ->with('success', 'Vaga criada com sucesso!');
    }

    /**
     * Exibe os detalhes de uma vaga específica.
     */
    public function show(string $id)
    {
        $spot = ParkingSpot::with(['parkingLot', 'currentSession.vehicle', 'currentReservation.user'])
            ->findOrFail($id);

        return view('parking-spots.show', compact('spot'));
    }

    /**
     * Exibe o formulário para editar uma vaga específica.
     */
    public function edit(string $id)
    {
        $spot = ParkingSpot::findOrFail($id);

        return view('parking-spots.edit', compact('spot'));
    }

    /**
     * Atualiza uma vaga específica.
     */
    public function update(Request $request, string $id)
    {
        $spot = ParkingSpot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'spot_identifier' => 'required|string|max:20',
            'zone' => 'nullable|string|max:20',
            'floor' => 'nullable|string|max:10',
            'is_reserved_for_disabled' => 'boolean',
            'is_reserved_for_electric' => 'boolean',
            'size' => 'integer|in:1,2,3',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verifica se já existe outra vaga com o mesmo identificador neste estacionamento
        $exists = ParkingSpot::where('parking_lot_id', $spot->parking_lot_id)
            ->where('spot_identifier', $request->spot_identifier)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'spot_identifier' => 'Já existe outra vaga com este identificador neste estacionamento.'
            ])->withInput();
        }

        $spot->update($request->all());

        return redirect()->route('parking-spots.show', $spot)
            ->with('success', 'Vaga atualizada com sucesso!');
    }

    /**
     * Marca uma vaga como em manutenção.
     */
    public function setMaintenance(Request $request, string $id)
    {
        $spot = ParkingSpot::findOrFail($id);

        if ($spot->status === 'occupied') {
            return back()->with('error', 'Não é possível colocar em manutenção uma vaga ocupada.');
        }

        $spot->setMaintenance($request->notes);

        return back()->with('success', 'Vaga marcada como em manutenção.');
    }

    /**
     * Marca uma vaga como disponível.
     */
    public function setAvailable(string $id)
    {
        $spot = ParkingSpot::findOrFail($id);

        if ($spot->status === 'occupied') {
            return back()->with('error', 'Não é possível liberar uma vaga ocupada.');
        }

        $spot->setAvailable();

        return back()->with('success', 'Vaga marcada como disponível.');
    }

    /**
     * Exibe o mapa interativo de vagas do estacionamento.
     */
    public function map(Request $request)
    {
        $parkingLot = ParkingLot::findOrFail($request->parking_lot_id);

        $floors = $parkingLot->parkingSpots()->distinct('floor')->pluck('floor');
        $zones = $parkingLot->parkingSpots()->distinct('zone')->pluck('zone');

        $currentFloor = $request->floor ?? $floors->first();

        return view('parking-spots.map', compact('parkingLot', 'floors', 'zones', 'currentFloor'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $spot = ParkingSpot::findOrFail($id);

        if ($spot->status === 'occupied') {
            return back()->with('error', 'Não é possível excluir uma vaga ocupada.');
        }

        $parkingLotId = $spot->parking_lot_id;

        $spot->delete();

        return redirect()->route('parking-spots.index', ['parking_lot_id' => $parkingLotId])
            ->with('success', 'Vaga excluída com sucesso!');
    }
}
