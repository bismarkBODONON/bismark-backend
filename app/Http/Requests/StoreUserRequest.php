<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            // Les comptes "entreprise" se créent via la gestion des entreprises clientes
            // (création simultanée de la fiche entreprise et association des solutions).
            'role' => ['required', Rule::in(['admin', 'technicien'])],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
