<?php

namespace App\Http\Controllers;

use App\Models\Volunteer;
use Illuminate\Http\Request;

class VolunteerController extends Controller
{
    public function index()
    {
        return Volunteer::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:volunteers,email',
            'phone' => 'nullable|string',
            'tax_code' => 'required|string|unique:volunteers,tax_code',
            'role' => 'required|string',
            'licenses' => 'nullable|string',
        ]);

        return Volunteer::create($data);
    }

    public function show(Volunteer $volunteer)
    {
        return $volunteer;
    }

    public function update(Request $request, Volunteer $volunteer)
    {
        $data = $request->validate([
            'first_name' => 'sometimes|required|string',
            'last_name' => 'sometimes|required|string',
            'email' => 'sometimes|required|email|unique:volunteers,email,' . $volunteer->id,
            'phone' => 'nullable|string',
            'tax_code' => 'sometimes|required|string|unique:volunteers,tax_code,' . $volunteer->id,
            'role' => 'sometimes|required|string',
            'licenses' => 'nullable|string',
        ]);

        $volunteer->update($data);
        return $volunteer;
    }

    public function destroy(Volunteer $volunteer)
    {
        $volunteer->delete();
        return response()->noContent();
    }
}
