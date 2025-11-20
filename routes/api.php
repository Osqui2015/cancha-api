<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Importar todos los controladores
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ======================================================================
// 1. RUTAS PÚBLICAS (No requieren autenticación)
// ======================================================================

// Autenticación básica
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Datos Maestros y Búsqueda (para que cualquiera pueda buscar cancha)
Route::get('/provinces', [SearchController::class, 'getProvinces']);
Route::get('/localities/{provinceId}', [SearchController::class, 'getLocalitiesByProvince']);
Route::get('/sports', [SearchController::class, 'getSports']);
Route::get('/search-courts', [SearchController::class, 'searchCourts']);

// Disponibilidad (pública para ver, pero reservar requiere login)
Route::get('/courts/{courtId}/availability', [BookingController::class, 'getAvailability']);


// ======================================================================
// 2. RUTAS PROTEGIDAS (Requieren Token de Usuario Logueado)
// ======================================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- Rutas Comunes para cualquier usuario logueado ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // --- Rutas de Cliente (Reservas) ---
    Route::get('/my-bookings', [BookingController::class, 'getUserBookings']); // Ver mis reservas
    Route::post('/bookings', [BookingController::class, 'store']); // Crear reserva


    // ==================================================================
    // 3. RUTAS DE PROPIETARIO (Requiere rol 'propietario')
    // ==================================================================
    Route::middleware('auth.owner')->prefix('owner')->group(function () {

        // Gestión de Complejos
        Route::get('/complexes', [OwnerController::class, 'getMyComplexes']);
        Route::post('/complexes', [OwnerController::class, 'storeComplex']);
        Route::get('/complexes/{complexId}', [OwnerController::class, 'showComplex']);
        Route::put('/complexes/{complexId}', [OwnerController::class, 'updateComplex']);
        Route::delete('/complexes/{complexId}', [OwnerController::class, 'destroyComplex']);

        // Gestión de Canchas (dentro de un complejo)
        Route::post('/complexes/{complexId}/courts', [OwnerController::class, 'storeCourt']);
        Route::get('/complexes/{complexId}/courts/{courtId}', [OwnerController::class, 'showCourt']);
        Route::put('/complexes/{complexId}/courts/{courtId}', [OwnerController::class, 'updateCourt']);
        Route::delete('/complexes/{complexId}/courts/{courtId}', [OwnerController::class, 'destroyCourt']);

        // Gestión de Reservas recibidas
        Route::get('/bookings', [OwnerController::class, 'getComplexBookings']);
        Route::patch('/bookings/{bookingId}/status', [OwnerController::class, 'updateBookingStatus']);
    });


    // ==================================================================
    // 4. RUTAS DE ADMINISTRADOR (Requiere rol 'admin')
    // ==================================================================
    Route::middleware('auth.admin')->prefix('admin')->group(function () {

        // Gestión de Usuarios
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/users', [AdminController::class, 'storeUser']);
        Route::put('/users/{userId}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{userId}', [AdminController::class, 'destroyUser']);

        // Gestión Global de Complejos y Canchas (Supervisión)
        Route::get('/complexes', [AdminController::class, 'getComplexes']);
        Route::delete('/complexes/{complexId}', [AdminController::class, 'destroyComplex']);
        Route::get('/courts', [AdminController::class, 'getCourts']);
        Route::delete('/courts/{courtId}', [AdminController::class, 'destroyCourt']);

        // Gestión Global de Reservas
        Route::get('/bookings', [AdminController::class, 'getBookings']);
        Route::patch('/bookings/{bookingId}/status', [AdminController::class, 'updateBookingStatus']);

        // Gestión de Datos Maestros (Deportes, Provincias, Localidades)
        Route::get('/sports', [AdminController::class, 'getSports']);
        Route::post('/sports', [AdminController::class, 'storeSport']);
        Route::put('/sports/{sportId}', [AdminController::class, 'updateSport']);
        Route::delete('/sports/{sportId}', [AdminController::class, 'destroySport']);

        Route::get('/provinces', [AdminController::class, 'getProvinces']);
        Route::post('/provinces', [AdminController::class, 'storeProvince']);
        Route::put('/provinces/{provinceId}', [AdminController::class, 'updateProvince']);
        Route::delete('/provinces/{provinceId}', [AdminController::class, 'destroyProvince']);

        Route::get('/localities', [AdminController::class, 'getLocalities']);
        Route::post('/localities', [AdminController::class, 'storeLocality']);
        Route::put('/localities/{localityId}', [AdminController::class, 'updateLocality']);
        Route::delete('/localities/{localityId}', [AdminController::class, 'destroyLocality']);
    });
});
