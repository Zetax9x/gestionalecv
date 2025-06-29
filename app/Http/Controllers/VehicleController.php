<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        return Vehicle::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'license_plate' => 'required|string|unique:vehicles,license_plate',
            'model' => 'nullable|string',
            'registration_date' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'inspection_expiry' => 'nullable|date',
        ]);

        return Vehicle::create($data);
    }

    public function show(Vehicle $vehicle)
    {
        return $vehicle;
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'type' => 'sometimes|required|string',
            'license_plate' => 'sometimes|required|string|unique:vehicles,license_plate,' . $vehicle->id,
            'model' => 'nullable|string',
            'registration_date' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'inspection_expiry' => 'nullable|date',
        ]);

        $vehicle->update($data);
        return $vehicle;
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return response()->noContent();
    }
}
