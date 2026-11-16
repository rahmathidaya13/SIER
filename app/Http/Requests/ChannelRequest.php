<?php

namespace App\Http\Requests;

use App\Models\ChannelModel;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ChannelRequest extends FormRequest
{
    public ?ChannelModel $channel = null;
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
        if ($this->isMethod('DELETE')) {
            return [];
        }
        $routeParam = $this->routeParameters();
        return [
            'name' => ['required', Rule::unique('channels', 'name')->ignore($routeParam, 'id_channel')],
            'code' => ['required', Rule::unique('channels', 'code')->ignore($routeParam, 'id_channel')],
            'icon' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama channel tidak boleh kosong',
            'name.unique' => 'Nama channel sudah ada',
            'code.required' => 'Kode channel tidak boleh kosong',
            'code.unique' => 'Kode channel sudah ada',
            'icon.required' => 'Icon channel harus dipilih',
        ];
    }
    public function routeParameters()
    {
        return $this->route('id') ?? $this->route('channel') ?? $this->route('channels');
    }
    public function resolveChannelModel()
    {
        if ($this->channel) {
            return $this->channel;
        }
        $routeParam = $this->routeParameters();
        if ($routeParam instanceof ChannelModel) {
            return $this->channel = $routeParam;
        }
        if ($routeParam) {
            return $this->channel = ChannelModel::findOrFail($routeParam);
        }
        return null;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            $channel = $this->resolveChannelModel();
            if (!$channel) {
                return;
            }

            if ($this->isMethod('DELETE') && $channel->leads()->exists()) {
                $validator->errors()->add(
                    'error',
                    'Tidak dapat menghapus sumber trafik ' . ucwords($channel->name) . ' karena masih memiliki data leads.'
                );
            }
        });
    }
}
