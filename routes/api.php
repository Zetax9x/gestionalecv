<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VolunteerController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\DocumentController;

Route::apiResource('volunteers', VolunteerController::class);
Route::apiResource('vehicles', VehicleController::class);
Route::apiResource('checklists', ChecklistController::class);
Route::apiResource('documents', DocumentController::class);
