<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index()
    {
        return Document::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'volunteer_id' => 'nullable|exists:volunteers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'category' => 'required|string',
            'name' => 'required|string',
            'path' => 'required|string',
            'expiry_date' => 'nullable|date',
        ]);

        return Document::create($data);
    }

    public function show(Document $document)
    {
        return $document;
    }

    public function update(Request $request, Document $document)
    {
        $data = $request->validate([
            'volunteer_id' => 'nullable|exists:volunteers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'category' => 'sometimes|required|string',
            'name' => 'sometimes|required|string',
            'path' => 'sometimes|required|string',
            'expiry_date' => 'nullable|date',
        ]);

        $document->update($data);
        return $document;
    }

    public function destroy(Document $document)
    {
        $document->delete();
        return response()->noContent();
    }
}
