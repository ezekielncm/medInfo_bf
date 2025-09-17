<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\{
    PatientsController,
    DoctorController,
    ConsultationController,
    LogsController
};

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'index']);
    Route::post('/admin/users', [AdminController::class, 'store']);
});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/patients', [PatientsController::class, 'index']);
    Route::get('/patients/{id}', [PatientsController::class, 'show']);
    Route::post('/patients', [PatientsController::class, 'store']);

    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::post('/doctors/{doctor}/assign-patient/{patient}', [DoctorController::class, 'assignPatient']);

    Route::get('/consultations/{id}', [ConsultationController::class, 'show']);
    Route::post('/consultations', [ConsultationController::class, 'store']);

    Route::get('/logs', [LogsController::class, 'index']);
});
