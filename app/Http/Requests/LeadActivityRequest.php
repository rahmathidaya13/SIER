<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadActivityRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => 'required|max:255'
        ];
    }
    public function messages()
    {
        return [
            'description.required' => 'Aktivitas Follow Up tidak boleh kosong',
            'description.max' => 'Aktivitas Follow Up maksimal 255 karakter'
        ];
    }
}
