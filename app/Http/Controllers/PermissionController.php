<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin');
    }

    /**
     * Mostra la matrice dei permessi
     */
    public function index()
    {
        $matrice = Permission::getMatricePermessi();
        $ruoli = Permission::RUOLI;
        $moduli = Permission::MODULI;

        return view('admin.permissions.index', compact('matrice', 'ruoli', 'moduli'));
    }

    /**
     * Aggiorna i permessi
     */
  public function update(Request $request)
{
    $validated = $request->validate([
        'permissions' => 'required|array',
        'permissions.*.modulo' => 'required|string',
        'permissions.*.ruolo' => 'required|string',
        'permissions.*.visualizza' => 'boolean',
        'permissions.*.crea' => 'boolean',
        'permissions.*.modifica' => 'boolean',
        'permissions.*.elimina' => 'boolean',
        'permissions.*.configura' => 'boolean',
    ]);

    DB::beginTransaction();

    try {
        foreach ($validated['permissions'] as $permissionData) {
            Permission::updateOrCreate(
                [
                    'modulo' => $permissionData['modulo'],
                    'ruolo' => $permissionData['ruolo']
                ],
                [
                    'visualizza' => $permissionData['visualizza'] ?? false,
                    'crea' => $permissionData['crea'] ?? false,
                    'modifica' => $permissionData['modifica'] ?? false,
                    'elimina' => $permissionData['elimina'] ?? false,
                    'configura' => $permissionData['configura'] ?? false,
                ]
            );
        }

        Permission::clearCache();
        DB::commit();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Permessi aggiornati con successo'
            ]);
        }

        return redirect()->back()->with('success', 'Permessi aggiornati con successo');

    } catch (\Exception $e) {
        DB::rollback();
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }

        return back()->withErrors(['error' => 'Errore nell\'aggiornamento: ' . $e->getMessage()]);
    }
}
}