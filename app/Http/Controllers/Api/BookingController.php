<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Court;
use App\Models\Booking;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function getAvailability(Request $request, int $courtId)
    {
        try {
            $request->validate([
                'date' => ['required', 'date_format:Y-m-d'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación en la fecha.',
                'errors' => $e->errors(),
            ], 422);
        }

        $court = Court::find($courtId);

        if (!$court) {
            return response()->json(['message' => 'Cancha no encontrada.'], 404);
        }

        $date = Carbon::parse($request->date);

        // Horario fijo simulado: 8 AM a 10 PM
        $startOfDay = $date->copy()->startOfDay()->addHours(8);
        $endOfDay = $date->copy()->startOfDay()->addHours(22);

        $availableSlots = [];
        $currentTime = $startOfDay->copy();

        $existingBookings = Booking::where('court_id', $courtId)
            ->whereDate('start_time', $date->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        while ($currentTime->lt($endOfDay)) {
            $slotEndTime = $currentTime->copy()->addHour();

            $isBooked = $existingBookings->where(function ($booking) use ($currentTime, $slotEndTime) {
                return $booking->start_time->lt($slotEndTime) && $booking->end_time->gt($currentTime);
            })->isNotEmpty();

            $isPast = $currentTime->lt(Carbon::now());

            $availableSlots[] = [
                'start_time' => $currentTime->format('H:i'),
                'end_time' => $slotEndTime->format('H:i'),
                'is_available' => !$isBooked && !$isPast,
                'price' => $court->price_per_hour,
            ];

            $currentTime->addHour();
        }

        return response()->json([
            'message' => 'Disponibilidad obtenida exitosamente.',
            'court' => $court->only(['id', 'name', 'price_per_hour']),
            'date' => $request->date,
            'availability' => $availableSlots,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'court_id' => ['required', 'integer', 'exists:courts,id'],
                'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
                'start_time_hour' => ['required', 'integer', 'min:0', 'max:23'],
                'duration_hours' => ['required', 'integer', 'min:1', 'max:4'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        }

        $court = Court::find($request->court_id);
        $user = $request->user();

        $startTime = Carbon::parse($request->date)->setHour($request->start_time_hour)->setMinute(0)->setSecond(0);
        $endTime = $startTime->copy()->addHours($request->duration_hours);

        if ($startTime->lt(Carbon::now())) {
            return response()->json(['message' => 'No puedes reservar en el pasado.'], 400);
        }

        $existingBooking = Booking::where('court_id', $court->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime->subSecond()])
                    ->orWhereBetween('end_time', [$startTime->addSecond(), $endTime])
                    ->orWhere(function ($query) use ($startTime, $endTime) {
                        $query->where('start_time', '<', $startTime)
                            ->where('end_time', '>', $endTime);
                    });
            })
            ->first();

        if ($existingBooking) {
            return response()->json(['message' => 'La cancha ya está reservada en ese horario.'], 409);
        }

        $totalPrice = $court->price_per_hour * $request->duration_hours;

        $booking = Booking::create([
            'user_id' => $user->id,
            'court_id' => $court->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'total_price' => $totalPrice,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Reserva creada exitosamente.',
            'booking' => $booking,
        ], 201);
    }

    public function getUserBookings(Request $request)
    {
        $bookings = $request->user()->bookings()->with(['court.complex', 'court.sport'])->get();
        return response()->json(['data' => $bookings]);
    }
}
