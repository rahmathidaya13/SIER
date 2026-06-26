<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Illuminate\Auth\Access\AuthorizationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof UnauthorizedException || $exception instanceof AuthorizationException) {
            // Redirect ke halaman lain, misalnya dashboard, dengan flash message
            return redirect()
                ->route('home') // Ganti dengan rute sesuai kebutuhanmu
                ->with('error', 'Anda tidak memiliki akses ke halaman tersebut');
        }
        return parent::render($request, $exception);
    }
}
