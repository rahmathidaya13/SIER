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
        $routeParam = $this->route('id') ?? $this->route('channel') ?? $this->route('channels');
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
    public function resolveChannelModel()
    {
        if ($this->channel) {
            return $this->channel;
        }
        // Cek parameter route (bisa bernama 'id', 'sale', atau 'sales' tergantung routes/web.php Anda)
        $routeParam = $this->route('id') ?? $this->route('channel') ?? $this->route('channels');
        if ($routeParam instanceof ChannelModel) {
            return $this->channel = $routeParam;
        }
        if ($routeParam) {
            return $this->channel = ChannelModel::findOrFail($routeParam);
        }
        return null;
    }
}
