<?php

namespace App\Http;

use App\Support\PanelLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPanelLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = PanelLocale::resolve($request->session()->get(PanelLocale::SESSION_KEY));

        app()->setLocale($locale);

        return $next($request);
    }
}
