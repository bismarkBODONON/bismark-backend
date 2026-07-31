<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'version', 'description', 'active'])]
class Solution extends Model
{
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_solution');
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'solution_technician');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
