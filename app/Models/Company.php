<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'contact_name', 'phone', 'address'])]
class Company extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function solutions(): BelongsToMany
    {
        return $this->belongsToMany(Solution::class, 'company_solution');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
