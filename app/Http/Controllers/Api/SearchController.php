<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Court;
use App\Models\Province;
use App\Models\Locality;
use App\Models\Sport;

class SearchController extends Controller
{
    public function searchCourts(Request $request)
    {
        try {
            $request->validate([
                'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
                'locality_id' => ['nullable', 'integer', 'exists:localities,id'],
                'sport_id' => ['nullable', 'integer', 'exists:sports,id'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación en los parámetros de búsqueda.',
                'errors' => $e->errors(),
            ], 422);
        }

        $courts = Court::query()
            ->with(['complex.locality.province', 'sport']);

        if ($request->filled('province_id')) {
            $courts->whereHas('complex.locality', function ($query) use ($request) {
                $query->where('province_id', $request->province_id);
            });
        }

        if ($request->filled('locality_id')) {
            $courts->whereHas('complex', function ($query) use ($request) {
                $query->where('locality_id', $request->locality_id);
            });
        }

        if ($request->filled('sport_id')) {
            $courts->where('sport_id', $request->sport_id);
        }

        $results = $courts->get();

        return response()->json([
            'message' => 'Canchas encontradas exitosamente.',
            'data' => $results,
        ]);
    }

    public function getProvinces()
    {
        $provinces = Province::all(['id', 'name']);
        return response()->json(['data' => $provinces]);
    }

    public function getLocalitiesByProvince($provinceId)
    {
        $localities = Locality::where('province_id', $provinceId)->get(['id', 'name']);
        return response()->json(['data' => $localities]);
    }

    public function getSports()
    {
        $sports = Sport::all(['id', 'name']);
        return response()->json(['data' => $sports]);
    }
}
