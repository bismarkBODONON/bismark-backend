<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Intervention;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function admin()
    {
        $statuses = ['declare', 'analyse', 'pris_en_charge', 'en_traitement', 'resolu', 'cloture'];
        $priorities = ['faible', 'moyenne', 'elevee', 'critique'];

        $parStatut = Incident::query()->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status');

        $parPriorite = Incident::query()->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')->pluck('total', 'priority');

        $topSolutions = Incident::query()
            ->join('solutions', 'solutions.id', '=', 'incidents.solution_id')
            ->select('solutions.name', DB::raw('count(*) as total'))
            ->groupBy('solutions.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => $row->total]);

        $topEntreprises = Incident::query()
            ->join('companies', 'companies.id', '=', 'incidents.company_id')
            ->select('companies.name', DB::raw('count(*) as total'))
            ->groupBy('companies.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => $row->total]);

        $topTechniciens = Incident::query()
            ->whereNotNull('technician_id')
            ->join('users', 'users.id', '=', 'incidents.technician_id')
            ->select('users.name', DB::raw('count(*) as total'))
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => $row->total]);

        // Temps moyen de résolution en heures (incidents résolus ou clôturés).
        $avgHours = Incident::whereNotNull('resolved_at')
            ->select(DB::raw('avg(timestampdiff(HOUR, created_at, resolved_at)) as avg_hours'))
            ->value('avg_hours');

        $currentMonth = Incident::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();
        $previousMonth = Incident::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)->count();

        $trend = null;
        if ($previousMonth > 0) {
            $trend = round((($currentMonth - $previousMonth) / $previousMonth) * 100);
        }

        return response()->json([
            'total_incidents' => Incident::count(),
            'en_cours' => Incident::whereIn('status', ['analyse', 'pris_en_charge', 'en_traitement'])->count(),
            'resolus' => Incident::where('status', 'resolu')->count(),
            'clotures' => Incident::where('status', 'cloture')->count(),
            'satisfaction_moyenne' => round((float) DB::table('evaluations')->avg('rating'), 1) ?: null,
            'temps_moyen_resolution_heures' => $avgHours !== null ? round((float) $avgHours, 1) : null,
            'tendance_mois' => $trend,
            'par_statut' => collect($statuses)->mapWithKeys(fn ($s) => [$s => (int) ($parStatut[$s] ?? 0)]),
            'par_priorite' => collect($priorities)->mapWithKeys(fn ($p) => [$p => (int) ($parPriorite[$p] ?? 0)]),
            'top_solutions' => $topSolutions,
            'top_entreprises' => $topEntreprises,
            'top_techniciens' => $topTechniciens,
        ]);
    }

    public function technicien(Request $request)
    {
        $user = $request->user();
        $solutionIds = $user->solutions()->pluck('solutions.id');

        $baseQuery = Incident::whereIn('solution_id', $solutionIds);

        $solutions = $user->solutions()
            ->withCount('incidents')
            ->orderBy('name')
            ->get(['solutions.id', 'solutions.name', 'solutions.version']);

        return response()->json([
            'assignes' => (clone $baseQuery)->where('technician_id', $user->id)->count(),
            'en_traitement' => (clone $baseQuery)->where('technician_id', $user->id)->where('status', 'en_traitement')->count(),
            'en_attente' => (clone $baseQuery)->whereNull('technician_id')->whereIn('status', ['declare', 'analyse'])->count(),
            'resolus_mois' => (clone $baseQuery)->where('technician_id', $user->id)
                ->where('status', 'resolu')
                ->whereMonth('resolved_at', now()->month)
                ->count(),
            'incidents_recents' => (clone $baseQuery)->with(['company:id,name', 'solution:id,name'])
                ->latest('updated_at')->limit(5)->get(),
            'interventions_recentes' => Intervention::where('technician_id', $user->id)
                ->with('incident:id,code,title')
                ->latest('date')->latest('id')->limit(5)->get(),
            'solutions' => $solutions,
        ]);
    }

    public function entreprise(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            return response()->json([
                'total_incidents' => 0, 'en_cours' => 0, 'resolus' => 0, 'clotures' => 0,
                'incidents_recents' => [], 'solutions' => [], 'derniers_messages' => [],
            ]);
        }

        $baseQuery = Incident::where('company_id', $company->id);

        $incidentIds = (clone $baseQuery)->pluck('id');

        $derniersMessages = Message::whereIn('incident_id', $incidentIds)
            ->with(['author:id,name', 'incident:id,code'])
            ->latest('created_at')
            ->limit(4)
            ->get()
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'incident_id' => $m->incident_id,
                'incident_code' => $m->incident?->code,
                'author_name' => $m->author?->name,
                'is_me' => $m->author_id === $user->id,
                'content' => $m->content,
                'created_at' => $m->created_at,
                'read_at' => $m->read_at,
            ]);

        return response()->json([
            'total_incidents' => (clone $baseQuery)->count(),
            'en_cours' => (clone $baseQuery)->whereIn('status', ['declare', 'analyse', 'pris_en_charge', 'en_traitement'])->count(),
            'resolus' => (clone $baseQuery)->where('status', 'resolu')->count(),
            'clotures' => (clone $baseQuery)->where('status', 'cloture')->count(),
            'incidents_recents' => (clone $baseQuery)->with(['solution:id,name'])
                ->latest('updated_at')->limit(5)->get(),
            'solutions' => $company->solutions()->orderBy('name')->get(['solutions.id', 'solutions.name', 'solutions.version', 'solutions.active']),
            'derniers_messages' => $derniersMessages,
        ]);
    }
}
