<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::query()
            ->withCount('solutions')
            ->with('user:id,name,email')
            ->orderBy('name')
            ->get()
            ->map(fn (Company $c) => $this->format($c));

        return response()->json($companies);
    }

    public function show(Company $company)
    {
        $company->load(['solutions', 'incidents', 'user']);

        return response()->json($this->format($company, withRelations: true));
    }

    public function store(StoreCompanyRequest $request)
    {
        $company = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'entreprise',
                'phone' => $request->phone,
            ]);

            return Company::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'contact_name' => $request->contact_name,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
        });

        return response()->json($this->format($company), 201);
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        DB::transaction(function () use ($request, $company) {
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ];

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $company->user->update($userData);

            $company->update([
                'name' => $request->name,
                'contact_name' => $request->contact_name,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
        });

        return response()->json($this->format($company->fresh(['user'])));
    }

    public function syncSolutions(Request $request, Company $company)
    {
        $request->validate([
            'solution_ids' => ['array'],
            'solution_ids.*' => ['integer', 'exists:solutions,id'],
        ]);

        $company->solutions()->sync($request->input('solution_ids', []));

        return response()->json($this->format($company->fresh('solutions'), withRelations: true));
    }

    private function format(Company $company, bool $withRelations = false): array
    {
        $data = [
            'id' => $company->id,
            'user_id' => $company->user_id,
            'name' => $company->name,
            'contact_name' => $company->contact_name,
            'phone' => $company->phone,
            'address' => $company->address,
            'email' => $company->user->email ?? null,
            'solutions_count' => $company->solutions_count ?? $company->solutions?->count(),
        ];

        if ($withRelations) {
            $data['solutions'] = $company->solutions;
            $data['incidents'] = $company->incidents;
        }

        return $data;
    }
}
