<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use Illuminate\Http\Request;

class AccessLogController extends Controller
{
    public function index()
    {
        return AccessLog::with('volunteer')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'volunteer_id' => 'required|exists:volunteers,id',
            'action' => 'required|string',
            'description' => 'nullable|string',
        ]);

        return AccessLog::create($data);
    }

    public function show(AccessLog $accessLog)
    {
        return $accessLog->load('volunteer');
    }

    public function update(Request $request, AccessLog $accessLog)
    {
        $data = $request->validate([
            'volunteer_id' => 'sometimes|required|exists:volunteers,id',
            'action' => 'sometimes|required|string',
            'description' => 'nullable|string',
        ]);

        $accessLog->update($data);
        return $accessLog->load('volunteer');
    }

    public function destroy(AccessLog $accessLog)
    {
        $accessLog->delete();
        return response()->noContent();
    }
}
