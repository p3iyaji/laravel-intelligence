<?php

namespace ProjectAnalyzer\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'project-analyzer::app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'appName' => config('app.name', 'Laravel'),
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
            ],
        ]);
    }
}
