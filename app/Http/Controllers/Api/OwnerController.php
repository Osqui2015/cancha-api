<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Booking;

class OwnerController extends Controller
{
    public function getMyComplexes(Request $request)
    {
        $complexes = $request->user()->complexes()->with('locality.province')->get();
        return response()->json(['data' => $complexes]);
    }

    public function storeComplex(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'locality_id' => ['required', 'integer', 'exists:localities,id'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error de validación', 'errors' => $e->errors()], 422);
        }

        $complex = $request->user()->complexes()->create([
            'name' => $request->name,
            'address' => $request->address,
            'description' => $request->description,
            'locality_id' => $request->locality_id,
        ]);

        return response()->json(['message' => 'Complejo creado.', 'data' => $complex], 201);
    }

    public function showComplex(Request $request, int $complexId)
    {
        $complex = $request->user()->complexes()->with('locality.province', 'courts.sport')->find($complexId);

        if (!$complex) {
            return response()->json(['message' => 'Complejo no encontrado.'], 404);
        }

        return response()->json(['data' => $complex]);
    }

    public function updateComplex(Request $request, int $complexId)
    {
        $complex = $request->user()->complexes()->find($complexId);
        if (!$complex) return response()->json(['message' => 'Complejo no encontrado.'], 404);

        $request->validate([
            'name' => ['required', 'string'],
            'address' => ['required', 'string'],
            'locality_id' => ['required', 'integer', 'exists:localities,id'],
        ]);

        $complex->update($request->only(['name', 'address', 'description', 'locality_id']));
        return response()->json(['message' => 'Actualizado.', 'data' => $complex]);
    }

    public function destroyComplex(Request $request, int $complexId)
    {
        $complex = $request->user()->complexes()->find($complexId);
        if (!$complex) return response()->json(['message' => 'Complejo no encontrado.'], 404);

        $complex->delete();
        return response()->json(['message' => 'Eliminado.']);
    }

    // --- Métodos para Canchas ---

    public function storeCourt(Request $request, int $complexId)
    {
        $complex = $request->user()->complexes()->find($complexId);
        if (!$complex) return response()->json(['message' => 'Complejo no encontrado.'], 404);

        $request->validate([
            'name' => ['required', 'string'],
            'sport_id' => ['required', 'integer', 'exists:sports,id'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
        ]);

        $court = $complex->courts()->create($request->only(['name', 'sport_id', 'surface_type', 'price_per_hour']));
        return response()->json(['message' => 'Cancha creada.', 'data' => $court], 201);
    }

    public function showCourt(Request $request, int $complexId, int $courtId)
    {
        $complex = $request->user()->complexes()->find($complexId);
        if (!$complex) return response()->json(['message' => 'Complejo no encontrado.'], 404);

        $court = $complex->courts()->with('sport')->find($courtId);
        if (!$court) return response()->json(['message' => 'Cancha no encontrada.'], 404);

        return response()->json(['data' => $court]);
    }

    public function updateCourt(Request $request, int $complexId, int $courtId)
    {
        $complex = $request->user()->complexes()->find($complexId);
        if (!$complex) return response()->json(['message' => 'Complejo no encontrado.'], 404);

        $court = $complex->courts()->find($courtId);
        if (!$court) return response()->json(['message' => 'Cancha no encontrada.'], 404);

        $request->validate([
            'name' => ['required', 'string'],
            'sport_id' => ['required', 'integer', 'exists:sports,id'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
        ]);

        $court->update($request->only(['name', 'sport_id', 'surface_type', 'price_per_hour']));
        return response()->json(['message' => 'Cancha actualizada.', 'data' => $court]);
    }

    public function destroyCourt(Request $request, int $complexId, int $courtId)
    {
        $complex = $request->user()->complexes()->find($complexId);
        if (!$complex) return response()->json(['message' => 'Complejo no encontrado.'], 404);

        $court = $complex->courts()->find($courtId);
        if (!$court) return response()->json(['message' => 'Cancha no encontrada.'], 404);

        $court->delete();
        return response()->json(['message' => 'Cancha eliminada.']);
    }

    // --- Métodos para Reservas ---

    public function getComplexBookings(Request $request)
    {
        $complexes = $request->user()->complexes()->with(['courts.bookings.user', 'courts.sport'])->get();

        $bookings = collect();
        foreach ($complexes as $complex) {
            foreach ($complex->courts as $court) {
                $bookings = $bookings->concat($court->bookings->map(function ($booking) use ($court, $complex) {
                    $booking->court_name = $court->name;
                    $booking->complex_name = $complex->name;
                    $booking->sport_name = $court->sport->name;
                    $booking->client_name = $booking->user->name;
                    return $booking;
                }));
            }
        }

        return response()->json(['data' => $bookings->sortBy('start_time')->values()]);
    }

    public function updateBookingStatus(Request $request, int $bookingId)
    {
        $request->validate(['status' => ['required', 'string', 'in:confirmed,cancelled']]);

        $booking = Booking::with('court.complex')->find($bookingId);

        if (!$booking || $booking->court->complex->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Reserva no encontrada o acceso denegado.'], 404);
        }

        $booking->status = $request->status;
        $booking->save();

        return response()->json(['message' => 'Estado actualizado.', 'data' => $booking]);
    }
}
