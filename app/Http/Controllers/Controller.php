<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Cek Policy via Gate::inspect dan lempar ValidationException jika ditolak
     * agar pesan error langsung tertangkap oleh form/alert Inertia di Vue.
     */
    protected function authorize(string $ability, mixed $arguments = [], string $flashType = 'error'): void
    {
        $response = Gate::inspect($ability, $arguments);

        if ($response->denied()) {
            $message = $response->message() ?: 'Anda tidak memiliki akses untuk tindakan ini.';

            // Jika request GET (buka halaman awal) dan berisiko redirect loop ke URL yang sama,
            // arahkan ke dashboard dengan pesan error
            if (request()->isMethod('GET') && url()->previous() === request()->fullUrl()) {
                abort(redirect()->route('home')->withErrors([$flashType => $message]));
            }

            throw ValidationException::withMessages([
                $flashType => $message,
            ]);
        }
    }
}
