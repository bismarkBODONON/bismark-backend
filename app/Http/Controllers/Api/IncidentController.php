<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncidentRequest;
use App\Models\Incident;
use App\Models\IncidentStatusHistory;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IncidentController extends Controller
{
    private const STATUS_FLOW = ['declare', 'analyse', 'pris_en_charge', 'en_traitement', 'resolu', 'cloture'];

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Incident::query()->with(['company:id,name', 'solution:id,name', 'technician:id,name']);

        if ($user->role === 'technicien') {
            $solutionIds = $user->solutions()->pluck('solutions.id');
            $query->whereIn('solution_id', $solutionIds);
        } elseif ($user->role === 'entreprise') {
            $query->where('company_id', $user->company?->id ?? 0);
        }

        return response()->json($query->latest('updated_at')->get());
    }

    public function show(Incident $incident)
    {
        $this->authorizeAccess($incident);

        $incident->load([
            'company:id,name',
            'solution:id,name',
            'technician:id,name',
            'interventions.technician:id,name',
            'evaluation',
        ]);

        return response()->json($incident);
    }

    public function store(StoreIncidentRequest $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            throw ValidationException::withMessages(['solution_id' => ['Aucune entreprise associée à ce compte.']]);
        }

        if (! $company->solutions()->where('solutions.id', $request->solution_id)->exists()) {
            throw ValidationException::withMessages(['solution_id' => ["Cette solution n'est pas associée à votre entreprise."]]);
        }

        $incident = DB::transaction(function () use ($request, $company) {
            $code = sprintf('INC-%s-%04d', now()->format('Y'), Incident::whereYear('created_at', now()->year)->count() + 1);

            $incident = Incident::create([
                'code' => $code,
                'title' => $request->title,
                'description' => $request->description,
                'category' => $request->category,
                'priority' => $request->priority,
                'status' => 'declare',
                'company_id' => $company->id,
                'solution_id' => $request->solution_id,
            ]);

            IncidentStatusHistory::create([
                'incident_id' => $incident->id,
                'old_status' => null,
                'new_status' => 'declare',
                'changed_by' => $company->user_id,
            ]);

            return $incident;
        });

        $solution = $incident->solution;
        foreach ($solution->technicians as $technician) {
            $this->notify($technician, 'nouvel_incident', 'Nouvel incident déclaré',
                "Un nouvel incident a été déclaré sur {$solution->name} : {$incident->title}");
        }

        return response()->json($incident, 201);
    }

    public function takeCharge(Incident $incident)
    {
        $user = Auth::user();

        if ($user->role !== 'technicien' || ! $user->solutions()->where('solutions.id', $incident->solution_id)->exists()) {
            return response()->json(['message' => "Vous n'êtes pas référent de la solution concernée."], 403);
        }

        if ($incident->technician_id) {
            return response()->json(['message' => 'Cet incident a déjà été pris en charge.'], 422);
        }

        if ($incident->status === 'cloture') {
            return response()->json(['message' => 'Cet incident est clôturé.'], 422);
        }

        $oldStatus = $incident->status;

        $incident->update([
            'technician_id' => $user->id,
            'status' => 'pris_en_charge',
        ]);

        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'old_status' => $oldStatus,
            'new_status' => 'pris_en_charge',
            'changed_by' => $user->id,
        ]);

        $this->notify($incident->company->user, 'prise_en_charge', 'Incident pris en charge',
            "Votre incident {$incident->code} a été pris en charge par {$user->name}.");

        return response()->json($incident->fresh(['technician']));
    }

    public function updateStatus(Request $request, Incident $incident)
    {
        $user = Auth::user();

        $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', self::STATUS_FLOW)],
        ]);

        if ($user->role !== 'technicien' || $incident->technician_id !== $user->id) {
            return response()->json(['message' => "Seul le technicien assigné peut modifier ce statut."], 403);
        }

        $currentIndex = array_search($incident->status, self::STATUS_FLOW);
        $requestedIndex = array_search($request->status, self::STATUS_FLOW);

        if ($requestedIndex !== $currentIndex + 1) {
            return response()->json([
                'message' => "Transition de statut non autorisée : {$incident->status} → {$request->status}.",
            ], 422);
        }

        $oldStatus = $incident->status;
        $updates = ['status' => $request->status];

        if ($request->status === 'resolu') {
            $updates['resolved_at'] = now();
        }
        if ($request->status === 'cloture') {
            $updates['closed_at'] = now();
        }

        $incident->update($updates);

        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'changed_by' => $user->id,
        ]);

        $label = match ($request->status) {
            'resolu' => 'résolu',
            'cloture' => 'clôturé',
            default => 'mis à jour',
        };

        $this->notify($incident->company->user, 'changement_statut', 'Statut mis à jour',
            "Votre incident {$incident->code} a été {$label}.");

        return response()->json($incident->fresh());
    }

    public function history(Incident $incident)
    {
        $this->authorizeAccess($incident);

        return response()->json(
            $incident->statusHistories()->with('changedBy:id,name')->orderBy('created_at')->get()
        );
    }

    private function authorizeAccess(Incident $incident): void
    {
        $user = Auth::user();

        if ($user->role === 'entreprise' && $incident->company_id !== $user->company?->id) {
            abort(403, "Vous n'avez pas accès à cet incident.");
        }

        if ($user->role === 'technicien' && ! $user->solutions()->where('solutions.id', $incident->solution_id)->exists()) {
            abort(403, "Vous n'avez pas accès à cet incident.");
        }
    }

    private function notify($user, string $type, string $title, string $message): void
    {
        if (! $user) {
            return;
        }

        Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }
}
