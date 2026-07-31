<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEvaluationRequest;
use App\Models\Incident;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    public function store(StoreEvaluationRequest $request, Incident $incident)
    {
        $user = Auth::user();

        if ($user->role !== 'entreprise' || $incident->company_id !== $user->company?->id) {
            return response()->json(['message' => "Vous n'avez pas accès à cet incident."], 403);
        }

        if (! in_array($incident->status, ['resolu', 'cloture'], true)) {
            return response()->json(['message' => "Seul un incident résolu ou clôturé peut être évalué."], 422);
        }

        if ($incident->evaluation) {
            return response()->json(['message' => 'Cet incident a déjà été évalué.'], 422);
        }

        $evaluation = $incident->evaluation()->create($request->validated());

        return response()->json($evaluation, 201);
    }
}
