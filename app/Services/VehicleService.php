<?php

namespace App\Services;

use App\Models\Vehicle;

class VehicleService
{
    public function all()
    {
        return Vehicle::all();
    }

    public function create(array $data)
    {
        return Vehicle::create($data);
    }

    public function update(Vehicle $vehicle, array $data)
    {
        $vehicle->update($data);
        return $vehicle;
    }

    public function delete(Vehicle $vehicle): void
    {
        $vehicle->delete();
    }
}
