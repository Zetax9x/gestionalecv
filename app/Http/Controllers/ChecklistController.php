<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function index()
    {
        return Checklist::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'compliant' => 'boolean',
        ]);

        return Checklist::create($data);
    }

    public function show(Checklist $checklist)
    {
        return $checklist;
    }

    public function update(Request $request, Checklist $checklist)
    {
        $data = $request->validate([
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'name' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'compliant' => 'boolean',
        ]);

        $checklist->update($data);
        return $checklist;
    }

    public function destroy(Checklist $checklist)
    {
        $checklist->delete();
        return response()->noContent();
    }
}
