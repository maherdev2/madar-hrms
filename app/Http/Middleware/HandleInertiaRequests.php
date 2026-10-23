<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\HrmsAccess;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'roles' => $request->user()?->roles->pluck('code') ?? [],
                'employee' => $request->user()?->employee?->only('id','first_name','last_name','department_id'),
                'isAdmin' => $request->user() ? app(HrmsAccess::class)->admin($request->user()) : false,
                'isManager' => $request->user() ? app(HrmsAccess::class)->manager($request->user()) : false,
            ],
            'flash' => ['success' => fn () => $request->session()->get('success')],
        ];
    }
}
