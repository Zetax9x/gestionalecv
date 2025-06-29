<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VolunteerController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DpiController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AccessLogController;

Route::apiResource('volunteers', VolunteerController::class);
Route::apiResource('vehicles', VehicleController::class);
Route::apiResource('checklists', ChecklistController::class);
Route::apiResource('documents', DocumentController::class);
Route::apiResource('dpis', DpiController::class);
Route::apiResource('roles', RoleController::class);
Route::apiResource('permissions', PermissionController::class);
Route::apiResource('access-logs', AccessLogController::class);
