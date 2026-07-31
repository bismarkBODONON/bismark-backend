<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInterventionRequest;
use App\Models\Incident;
use Illuminate\Support\Facades\Auth;

class InterventionController extends Controller
{
    public function index(Incident $incident)
    {
        return response()->json(
            $incident->interventions()->with('technician:id,name')->orderByDesc('date')->get()
        );
    }

    public function store(StoreInterventionRequest $request, Incident $incident)
    {
        $user = Auth::user();

        if ($user->role !== 'technicien' || $incident->technician_id !== $user->id) {
            return response()->json(['message' => "Seul le technicien assigné peut ajouter une intervention."], 403);
        }

        $intervention = $incident->interventions()->create([
            ...$request->validated(),
            'technician_id' => $user->id,
        ]);

        return response()->json($intervention->load('technician:id,name'), 201);
    }
}
