<?php

namespace App\Http\Controllers;

use App\Models\ParkingLot;
use App\Models\ParkingReservation;
use App\Models\ParkingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ParkingReservationController extends Controller
{
    /**
     * Exibe a lista de reservas para o usuário atual.
     */
    public function index()
    {
        $reservations = ParkingReservation::where('user_id', Auth::id())
            ->orderBy('start_time', 'desc')
            ->paginate(10);

        return view('reservations.index', compact('reservations'));
    }

    /**
     * Mostra o formulário para criar uma nova reserva.
     */
    public function create()
    {
        $parkingLots = ParkingLot::where('available_spots', '>', 0)->get();
        return view('reservations.create', compact('parkingLots'));
    }

    /**
     * Verifica a disponibilidade de vagas para um período específico.
     */
    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parking_lot_id' => 'required|exists:parking_lots,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $parkingLot = ParkingLot::findOrFail($request->parking_lot_id);
        $startTime = new \DateTime($request->start_time);
        $endTime = new \DateTime($request->end_time);

        $isAvailable = $parkingLot->hasAvailableSpotsForPeriod($startTime, $endTime);
        $estimatedTotal = $parkingLot->calculateStandardParkingFee($startTime, $endTime);

        return response()->json([
            'success' => true,
            'is_available' => $isAvailable,
            'available_spots' => $parkingLot->getAvailableSpotsAt($startTime),
            'estimated_total' => $estimatedTotal,
            'reservation_fee' => 10.00, // Taxa fixa de reserva
            'discount' => 0, // Será calculado se houver código de desconto
            'total' => $estimatedTotal + 10.00, // Total estimado + taxa de reserva
        ]);
    }

    /**
     * Armazena uma nova reserva.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parking_lot_id' => 'required|exists:parking_lots,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'vehicle_plate' => 'required|string|max:10',
            'vehicle_model' => 'required|string|max:50',
            'vehicle_color' => 'required|string|max:20',
            'discount_code' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $parkingLot = ParkingLot::findOrFail($request->parking_lot_id);
        $startTime = new \DateTime($request->start_time);
        $endTime = new \DateTime($request->end_time);

        // Verifica novamente a disponibilidade (para evitar race conditions)
        if (!$parkingLot->hasAvailableSpotsForPeriod($startTime, $endTime)) {
            return back()->with('error', 'Desculpe, não há mais vagas disponíveis para o período selecionado.')->withInput();
        }

        // Cria a reserva
        $vehicleDetails = [
            'plate' => $request->vehicle_plate,
            'model' => $request->vehicle_model,
            'color' => $request->vehicle_color,
        ];

        try {
            $reservation = ParkingReservation::createReservation(
                $request->parking_lot_id,
                Auth::id(),
                $startTime,
                $endTime,
                $vehicleDetails,
                $request->discount_code
            );

            return redirect()->route('reservations.show', $reservation)
                ->with('success', 'Sua reserva foi criada com sucesso!');

        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao criar sua reserva: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Exibe os detalhes de uma reserva.
     */
    public function show(ParkingReservation $reservation)
    {
        // Verifica se o usuário atual é o dono da reserva
        if ($reservation->user_id !== Auth::id()) {
            abort(403, 'Acesso não autorizado.');
        }

        return view('reservations.show', compact('reservation'));
    }

    /**
     * Cancela uma reserva.
     */
    public function cancel(Request $request, ParkingReservation $reservation)
    {
        // Verifica se o usuário atual é o dono da reserva
        if ($reservation->user_id !== Auth::id()) {
            abort(403, 'Acesso não autorizado.');
        }

        // Verifica se a reserva já foi concluída ou cancelada
        if ($reservation->status !== 'pending' && $reservation->status !== 'confirmed') {
            return back()->with('error', 'Esta reserva não pode ser cancelada.');
        }

        $reservation->cancel($request->reason ?? 'Cancelado pelo cliente');

        return redirect()->route('reservations.index')
            ->with('success', 'Sua reserva foi cancelada com sucesso.');
    }

    /**
     * Realiza o check-in para uma reserva (uso interno).
     */
    public function checkIn(Request $request, ParkingReservation $reservation)
    {
        // Esta rota é apenas para uso interno ou API
        // Verifica permissões adequadas (operador, admin, etc.)

        // Verifica se a reserva pode ter check-in
        if ($reservation->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Esta reserva não está confirmada para check-in.'
            ], 422);
        }

        // Valida veículo
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Cria a sessão de estacionamento com a reserva associada
            $session = ParkingSession::createSession(
                $reservation->parking_lot_id,
                $request->vehicle_id,
                $reservation->user_id,
                $reservation->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-in realizado com sucesso.',
                'session_id' => $session->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao realizar check-in: ' . $e->getMessage()
            ], 500);
        }
    }
}
