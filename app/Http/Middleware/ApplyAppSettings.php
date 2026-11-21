<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyAppSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        Setting::applyRuntime();

        return $next($request);
    }
}
