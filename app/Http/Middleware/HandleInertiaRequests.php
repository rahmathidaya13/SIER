<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => auth()->user()?->load(['roles', 'permissions', 'branch', 'profile']) ?? null,
                'user_id' => $request->user() ? $request->user()->id : null,
            ],
            'flash' => fn() => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
                'danger' => $request->session()->get('danger'),
                'message' => $request->session()->get('message'),
                'status' => $request->session()->get('status'),
                'alert_error' => $request->session()->get('alert_error'),

                'modal_message' => fn() => $request->session()->get('modal_message'),

                'action_label' => fn() => $request->session()->get('action_label'),
                'action_url' => fn() => $request->session()->get('action_url'),
                'unauthorized_action' => fn() => $request->session()->get('unauthorized_action'),

                'new_id_lead' => fn() => $request->session()->get('new_id_lead'), // Menyimpan ID lead baru yang ditambahkan

                'activity_message' => fn() => $request->session()->get('activity_message'),
            ],
            'old' => fn() => session()->getOldInput(),
            'path' => fn() => $request->path(),
            'fullUrl' => fn() => $request->fullUrl(),
            'app' => [
                'name' => config('app.name'),
                'version' => '1.0.0',
            ],
            'locale' => fn() => app()->getLocale(),
        ]);
    }
}
