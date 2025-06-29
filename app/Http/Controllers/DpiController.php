<?php

namespace App\Http\Controllers;

use App\Models\Dpi;
use Illuminate\Http\Request;

class DpiController extends Controller
{
    public function index()
    {
        return Dpi::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'volunteer_id' => 'required|exists:volunteers,id',
            'name' => 'required|string',
            'assigned_at' => 'required|date',
            'expiry_date' => 'nullable|date',
        ]);

        return Dpi::create($data);
    }

    public function show(Dpi $dpi)
    {
        return $dpi;
    }

    public function update(Request $request, Dpi $dpi)
    {
        $data = $request->validate([
            'volunteer_id' => 'sometimes|required|exists:volunteers,id',
            'name' => 'sometimes|required|string',
            'assigned_at' => 'sometimes|required|date',
            'expiry_date' => 'nullable|date',
        ]);

        $dpi->update($data);
        return $dpi;
    }

    public function destroy(Dpi $dpi)
    {
        $dpi->delete();
        return response()->noContent();
    }
}
