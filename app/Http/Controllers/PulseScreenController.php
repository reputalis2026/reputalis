<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CsatSurvey;
use App\Support\Pulse\PulseMetrics;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PulseScreenController extends Controller
{
    public function __construct(private readonly PulseMetrics $metrics) {}

    public function entry(string $clientCode): View
    {
        return $this->home(request(), $clientCode, true);
    }

    public function home(Request $request, string $clientCode, bool $launch = false): View
    {
        $client = $this->client($clientCode);

        return view('pulse.home', array_merge($this->frame($client, 'Inicio', null, false), [
            'home' => $this->metrics->home($client, $this->readSurveys($request)),
            'externalError' => $this->metrics->externalError($client),
            'links' => $this->links($client),
            'pulseBoot' => $launch ? 'launch' : 'app',
        ]));
    }

    public function interna(Request $request, string $clientCode): View
    {
        $client = $this->client($clientCode);
        $period = $this->period($request, 'interna');

        return view('pulse.interna', array_merge($this->frame($client, 'Reputación interna'), [
            'period' => $period,
            'summary' => $this->metrics->internalSummary($client->id, $period),
            'months' => $this->metrics->internalMonths($client->id),
        ]));
    }

    public function externa(Request $request, string $clientCode): View
    {
        $client = $this->client($clientCode);
        $period = $this->period($request, 'externa');

        return view('pulse.externa', array_merge($this->frame($client, 'Reputación externa'), [
            'period' => $period,
            'summary' => $this->metrics->externalSummary($client, $period),
            'months' => $this->metrics->externalMonths($client),
            'error' => $this->metrics->externalError($client),
        ]));
    }

    public function sector(string $clientCode): View
    {
        $client = $this->client($clientCode);

        return view('pulse.sector', $this->frame($client, 'Comparativa sector'));
    }

    public function sectorLista(string $clientCode): View
    {
        $client = $this->client($clientCode);

        return view('pulse.sector-lista', array_merge($this->frame($client, 'Comparativa sector', url('/pulse/'.$client->code.'/sector')), []));
    }

    public function equipo(string $clientCode): View
    {
        $client = $this->client($clientCode);

        return view('pulse.equipo', array_merge($this->frame($client, 'Equipo'), [
            'team' => $this->metrics->team($client),
        ]));
    }

    public function empleado(Request $request, string $clientCode, string $employee): View
    {
        $client = $this->client($clientCode);
        $period = $this->period($request, 'empleado');
        $detail = $this->metrics->employee($client, $employee, $period);
        abort_if($detail === null, 404);

        return view('pulse.empleado', array_merge($this->frame($client, $detail['name'], url('/pulse/'.$client->code.'/equipo')), [
            'period' => $period,
            'employee' => $detail,
        ]));
    }

    public function mejoras(Request $request, string $clientCode): View
    {
        $client = $this->client($clientCode);
        $period = $this->period($request, 'mejoras');

        return view('pulse.mejoras', array_merge($this->frame($client, 'Puntos de mejora'), [
            'period' => $period,
            'report' => $this->metrics->improvements($client, $period),
        ]));
    }

    public function mejora(Request $request, string $clientCode, string $option): View
    {
        $client = $this->client($clientCode);
        $period = $this->period($request, 'mejoras');
        $detail = $this->metrics->improvementDetail($client, $option, $period);
        abort_if($detail === null, 404);

        return view('pulse.mejora', array_merge($this->frame($client, $detail['label'], url('/pulse/'.$client->code.'/mejoras')), [
            'period' => $period,
            'detail' => $detail,
        ]));
    }

    public function alertas(Request $request, string $clientCode): View
    {
        $client = $this->client($clientCode);
        $filter = $request->query('filtro') === 'sin_leer' ? 'sin_leer' : 'todas';

        return view('pulse.alertas', array_merge($this->frame($client, 'Alertas'), [
            'filter' => $filter,
            'alerts' => $this->metrics->alerts($client, $this->readSurveys($request), $filter === 'sin_leer'),
        ]));
    }

    public function readAlert(string $clientCode, string $alert): Response
    {
        $client = $this->client($clientCode);
        $row = $client->externalReputationAlerts()->whereKey($alert)->firstOrFail();
        $row->markAsRead();

        return response()->noContent();
    }

    public function readSurveyAlert(Request $request, string $clientCode, string $survey): Response
    {
        $client = $this->client($clientCode);
        $exists = CsatSurvey::query()
            ->where('client_id', $client->id)
            ->whereKey($survey)
            ->whereIn('score', [1, 2, 3])
            ->exists();
        abort_unless($exists, 404);

        $ids = $this->readSurveys($request);
        $ids[] = $survey;
        $request->session()->put('pulse_read_surveys', array_values(array_unique($ids)));

        return response()->noContent();
    }

    public function notificaciones(string $clientCode): View
    {
        $client = $this->client($clientCode);

        return view('pulse.notificaciones', $this->frame($client, 'Notificaciones'));
    }

    public function soporte(string $clientCode): View
    {
        $client = $this->client($clientCode);
        $contact = $client->distributorSupportContact();

        return view('pulse.soporte', array_merge($this->frame($client, 'Soporte'), [
            'phone' => $contact['phone'],
            'email' => $contact['email'],
        ]));
    }

    private function client(string $clientCode): Client
    {
        $user = Auth::user();
        if (! $user || ! $user->isClientOwner() || ! $user->ownedClient || $user->ownedClient->code !== $clientCode) {
            abort(403);
        }

        $client = $user->ownedClient;
        if (! $client->is_active) {
            abort(403);
        }

        return $client;
    }

    /**
     * @return array<string, mixed>
     */
    private function frame(Client $client, string $heading, ?string $back = null, bool $chrome = true): array
    {
        return [
            'client' => $client,
            'showChrome' => $chrome,
            'heading' => $heading,
            'backUrl' => $back ?? url('/pulse/'.$client->code.'/inicio'),
            'homeUrl' => url('/pulse/'.$client->code.'/inicio'),
            'title' => $heading.' — El Pulso del Día',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function links(Client $client): array
    {
        $base = url('/pulse/'.$client->code);

        return [
            'interna' => $base.'/interna',
            'externa' => $base.'/externa',
            'sector' => $base.'/sector',
            'equipo' => $base.'/equipo',
            'mejoras' => $base.'/mejoras',
            'alertas' => $base.'/alertas',
            'notificaciones' => $base.'/notificaciones',
            'soporte' => $base.'/soporte',
        ];
    }

    private function period(Request $request, string $module): string
    {
        $key = 'pulse_period.'.$module;
        $incoming = trim((string) $request->query('periodo', ''));
        if (in_array($incoming, PulseMetrics::PERIODS, true)) {
            $request->session()->put($key, $incoming);
            $request->session()->save();
        }

        $stored = (string) $request->session()->get($key, 'acumulado');

        return in_array($stored, PulseMetrics::PERIODS, true) ? $stored : 'acumulado';
    }

    /**
     * @return array<int, string>
     */
    private function readSurveys(Request $request): array
    {
        return array_values(array_filter(
            (array) $request->session()->get('pulse_read_surveys', []),
            fn ($id): bool => is_string($id) && $id !== ''
        ));
    }
}
