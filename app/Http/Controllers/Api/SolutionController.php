<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSolutionRequest;
use App\Http\Requests\UpdateSolutionRequest;
use App\Models\Solution;
use Illuminate\Http\Request;

class SolutionController extends Controller
{
    public function index()
    {
        return response()->json(Solution::orderBy('name')->get());
    }

    public function show(Solution $solution)
    {
        $solution->load(['technicians:id,name,email', 'companies:id,name', 'incidents:id,code,title,status,solution_id']);

        return response()->json($solution);
    }

    public function store(StoreSolutionRequest $request)
    {
        $solution = Solution::create($request->validated());

        return response()->json($solution, 201);
    }

    public function update(UpdateSolutionRequest $request, Solution $solution)
    {
        $solution->update($request->validated());

        return response()->json($solution);
    }

    public function syncTechnicians(Request $request, Solution $solution)
    {
        $request->validate([
            'technician_ids' => ['array'],
            'technician_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $solution->technicians()->sync($request->input('technician_ids', []));

        return response()->json($solution->fresh('technicians'));
    }

    public function mine(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'technicien') {
            return response()->json($user->solutions()->orderBy('name')->get());
        }

        if ($user->role === 'entreprise') {
            $solutions = $user->company?->solutions()->orderBy('name')->get() ?? collect();

            return response()->json($solutions);
        }

        return response()->json([]);
    }
}
