<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Complex;
use App\Models\Court;
use App\Models\Province;
use App\Models\Locality;
use App\Models\Sport;
use App\Models\Booking;

class AdminController extends Controller
{
    // --- Usuarios ---
    public function getUsers()
    {
        return response()->json(['data' => User::all()->makeHidden(['password', 'remember_token'])]);
    }

    public function storeUser(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8'],
                'role' => ['required', 'string', 'in:cliente,propietario,admin'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error de validación', 'errors' => $e->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json(['message' => 'Usuario creado.', 'data' => $user->makeHidden(['password', 'remember_token'])], 201);
    }

    public function updateUser(Request $request, int $userId)
    {
        $user = User::find($userId);
        if (!$user) return response()->json(['message' => 'Usuario no encontrado.'], 404);

        $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'unique:users,email,' . $userId],
            'role' => ['required', 'string', 'in:cliente,propietario,admin'],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return response()->json(['message' => 'Usuario actualizado.', 'data' => $user->makeHidden(['password'])]);
    }

    public function destroyUser(int $userId)
    {
        $user = User::find($userId);
        if (!$user) return response()->json(['message' => 'Usuario no encontrado.'], 404);
        $user->delete();
        return response()->json(['message' => 'Usuario eliminado.']);
    }

    // --- Complejos y Canchas Globales ---
    public function getComplexes()
    {
        return response()->json(['data' => Complex::with('owner', 'locality.province')->get()]);
    }

    public function destroyComplex(int $complexId)
    {
        $complex = Complex::find($complexId);
        if (!$complex) return response()->json(['message' => 'No encontrado.'], 404);
        $complex->delete();
        return response()->json(['message' => 'Eliminado.']);
    }

    public function getCourts()
    {
        return response()->json(['data' => Court::with('complex.owner', 'sport')->get()]);
    }

    public function destroyCourt(int $courtId)
    {
        $court = Court::find($courtId);
        if (!$court) return response()->json(['message' => 'No encontrado.'], 404);
        $court->delete();
        return response()->json(['message' => 'Eliminado.']);
    }

    // --- Reservas Globales ---
    public function getBookings()
    {
        return response()->json(['data' => Booking::with('user', 'court.complex', 'court.sport')->get()]);
    }

    public function updateBookingStatus(Request $request, int $bookingId)
    {
        $booking = Booking::find($bookingId);
        if (!$booking) return response()->json(['message' => 'Reserva no encontrada.'], 404);

        $request->validate(['status' => ['required', 'string', 'in:pending,confirmed,cancelled']]);
        $booking->status = $request->status;
        $booking->save();
        return response()->json(['message' => 'Estado actualizado.', 'data' => $booking]);
    }

    // --- Deportes ---
    public function getSports()
    {
        return response()->json(['data' => Sport::all()]);
    }

    public function storeSport(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'unique:sports,name']]);
        $sport = Sport::create(['name' => $request->name]);
        return response()->json(['message' => 'Deporte creado.', 'data' => $sport], 201);
    }

    public function updateSport(Request $request, int $sportId)
    {
        $sport = Sport::find($sportId);
        if (!$sport) return response()->json(['message' => 'No encontrado.'], 404);
        $request->validate(['name' => ['required', 'string', 'unique:sports,name,' . $sportId]]);
        $sport->update(['name' => $request->name]);
        return response()->json(['message' => 'Actualizado.', 'data' => $sport]);
    }

    public function destroySport(int $sportId)
    {
        $sport = Sport::find($sportId);
        if (!$sport) return response()->json(['message' => 'No encontrado.'], 404);
        $sport->delete();
        return response()->json(['message' => 'Eliminado.']);
    }

    // --- Provincias ---
    public function getProvinces()
    {
        return response()->json(['data' => Province::all()]);
    }

    public function storeProvince(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'unique:provinces,name']]);
        $province = Province::create(['name' => $request->name]);
        return response()->json(['message' => 'Provincia creada.', 'data' => $province], 201);
    }

    public function updateProvince(Request $request, int $provinceId)
    {
        $province = Province::find($provinceId);
        if (!$province) return response()->json(['message' => 'No encontrada.'], 404);
        $request->validate(['name' => ['required', 'string', 'unique:provinces,name,' . $provinceId]]);
        $province->update(['name' => $request->name]);
        return response()->json(['message' => 'Actualizada.', 'data' => $province]);
    }

    public function destroyProvince(int $provinceId)
    {
        $province = Province::find($provinceId);
        if (!$province) return response()->json(['message' => 'No encontrada.'], 404);
        $province->delete();
        return response()->json(['message' => 'Eliminada.']);
    }

    // --- Localidades ---
    public function getLocalities()
    {
        return response()->json(['data' => Locality::with('province')->get()]);
    }

    public function storeLocality(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'province_id' => ['required', 'integer', 'exists:provinces,id']
        ]);
        $locality = Locality::create($request->only(['name', 'province_id']));
        return response()->json(['message' => 'Localidad creada.', 'data' => $locality], 201);
    }

    public function updateLocality(Request $request, int $localityId)
    {
        $locality = Locality::find($localityId);
        if (!$locality) return response()->json(['message' => 'No encontrada.'], 404);
        $request->validate([
            'name' => ['required', 'string'],
            'province_id' => ['required', 'integer', 'exists:provinces,id']
        ]);
        $locality->update($request->only(['name', 'province_id']));
        return response()->json(['message' => 'Actualizada.', 'data' => $locality]);
    }

    public function destroyLocality(int $localityId)
    {
        $locality = Locality::find($localityId);
        if (!$locality) return response()->json(['message' => 'No encontrada.'], 404);
        $locality->delete();
        return response()->json(['message' => 'Eliminada.']);
    }
}
