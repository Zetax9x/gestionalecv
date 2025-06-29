<?php

namespace App\Services;

use App\Models\Volunteer;

class VolunteerService
{
    public function all()
    {
        return Volunteer::all();
    }

    public function create(array $data)
    {
        return Volunteer::create($data);
    }

    public function update(Volunteer $volunteer, array $data)
    {
        $volunteer->update($data);
        return $volunteer;
    }

    public function delete(Volunteer $volunteer): void
    {
        $volunteer->delete();
    }
}
