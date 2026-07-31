<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,mp4,mov', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required_without' => 'Le message doit contenir du texte ou une pièce jointe.',
            'attachment.mimes' => 'Formats acceptés : jpg, png, webp, mp4, mov.',
            'attachment.max' => 'Le fichier ne doit pas dépasser 20 Mo.',
        ];
    }
}