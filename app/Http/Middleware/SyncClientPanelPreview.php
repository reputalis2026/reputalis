<?php

namespace App\Http\Middleware;

use App\Support\ClientPanel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncClientPanelPreview
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isClientOwner()) {
            return $next($request);
        }

        if ($request->routeIs('livewire.*') || $request->is('livewire/*')) {
            return $next($request);
        }

        if (ClientPanel::isClientDashboardRoute()) {
            $client = ClientPanel::clientFromRoute();

            if ($client) {
                ClientPanel::enterPreview($client);
            }

            return $next($request);
        }

        if (ClientPanel::isClientExperienceRoute() && ClientPanel::isPreview()) {
            return $next($request);
        }

        if (
            ClientPanel::isEmployeeResourceRoute()
            || ClientPanel::isClientEmployeesCardRoute()
            || ClientPanel::isClientFichaRoute()
            || ClientPanel::isClientEditRoute()
        ) {
            $client = ClientPanel::clientFromRoute();

            if ($client) {
                ClientPanel::enterPreview($client);
            }

            return $next($request);
        }

        ClientPanel::exitPreview();

        return $next($request);
    }
}
