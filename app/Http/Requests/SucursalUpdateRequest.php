<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SucursalUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string'],
            'direccion' => ['required', 'string'],
            'ciudad' => ['required', 'string'],
            'telefono' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'unique:sucursals,email'],
            'activa' => ['required'],
        ];
    }
}
