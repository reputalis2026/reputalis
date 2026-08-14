<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Support\ExternalReputation\ExternalReputationSyncService;
use Illuminate\Console\Command;

class SyncExternalReputation extends Command
{
    protected $signature = 'external-reputation:sync
                            {--client= : UUID o code del cliente}
                            {--only-missing : Solo clientes sin ningún snapshot}
                            {--dry-run : Listar clientes sin sincronizar}';

    protected $description = 'Sincroniza reputación Google (Outscraper Places) y guarda snapshots/alertas';

    public function handle(ExternalReputationSyncService $sync): int
    {
        $query = Client::query()
            ->whereNotNull('google_place_id')
            ->where('google_place_id', '!=', '')
            ->orderBy('code');

        $clientOpt = trim((string) $this->option('client'));
        if ($clientOpt !== '') {
            if (\Illuminate\Support\Str::isUuid($clientOpt)) {
                $query->where('id', $clientOpt);
            } else {
                $query->where('code', $clientOpt);
            }
        }

        if ($this->option('only-missing')) {
            $query->whereDoesntHave('externalReputationSnapshots');
        }

        $clients = $query->get();

        if ($clients->isEmpty()) {
            $this->warn('No hay clientes con Place ID que coincidan los filtros.');

            return self::SUCCESS;
        }

        $this->info('Clientes a sincronizar: '.$clients->count());
        $driver = (string) config('services.outscraper.driver', 'fake');
        $this->line('Driver Outscraper: '.$driver);

        if ($this->option('dry-run')) {
            foreach ($clients as $client) {
                $this->line(sprintf(
                    '- %s (%s) place_id=%s last_sync=%s',
                    $client->code,
                    $client->namecommercial,
                    $client->google_place_id,
                    $client->external_reputation_last_synced_at?->toDateTimeString() ?? 'nunca'
                ));
            }

            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;

        foreach ($clients as $client) {
            $result = $sync->syncClient($client);

            if ($result['ok']) {
                $ok++;
                $alertNote = $result['alert'] ? ' [alerta 1★/2★]' : '';
                $this->info("OK {$client->code}{$alertNote}");
            } else {
                $failed++;
                $this->error("FAIL {$client->code}: ".($result['error'] ?? 'error desconocido'));
            }
        }

        $this->newLine();
        $this->info("Resumen: ok={$ok} failed={$failed}");

        return $failed > 0 && $ok === 0 ? self::FAILURE : self::SUCCESS;
    }
}
