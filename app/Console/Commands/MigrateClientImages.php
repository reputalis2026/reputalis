<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Employee;
use App\Support\ClientImagePaths;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateClientImages extends Command
{
    protected $signature = 'clients:migrate-images {--dry-run : Solo mostrar cambios sin mover archivos}';

    protected $description = 'Mueve logos/fotos a img/{code}/logo y img/{code}/employees/{employee_id}';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $disk = Storage::disk('public');
        $moved = 0;

        Client::query()
            ->whereNotNull('logo')
            ->where('logo', '!=', '')
            ->orderBy('code')
            ->each(function (Client $client) use ($disk, $dryRun, &$moved): void {
                $old = (string) $client->logo;
                $filename = basename($old);
                $new = ClientImagePaths::logoDirectory($client->code).'/'.$filename;

                if ($old === $new || str_starts_with($old, ClientImagePaths::logoDirectory($client->code).'/')) {
                    return;
                }

                if (! $disk->exists($old)) {
                    $this->warn("Logo no encontrado [{$client->code}]: {$old}");

                    return;
                }

                $this->line(($dryRun ? '[dry-run] ' : '')."Logo {$client->code}: {$old} → {$new}");

                if (! $dryRun) {
                    $disk->makeDirectory(dirname($new));
                    if ($disk->exists($new)) {
                        $disk->delete($new);
                    }
                    $disk->move($old, $new);
                    $client->forceFill(['logo' => $new])->saveQuietly();
                }

                $moved++;
            });

        Employee::query()
            ->with('client:id,code')
            ->whereNotNull('photo')
            ->where('photo', '!=', '')
            ->orderBy('id')
            ->each(function (Employee $employee) use ($disk, $dryRun, &$moved): void {
                if (! $employee->client?->code) {
                    return;
                }

                $old = (string) $employee->photo;
                $targetDir = ClientImagePaths::employeeDirectory($employee->client->code, (string) $employee->id);
                $new = $targetDir.'/'.basename($old);

                if ($old === $new) {
                    return;
                }

                if (! $disk->exists($old)) {
                    $this->warn("Foto no encontrada [empleado {$employee->id}]: {$old}");

                    return;
                }

                $this->line(($dryRun ? '[dry-run] ' : '')."Foto {$employee->client->code}: {$old} → {$new}");

                if (! $dryRun) {
                    $disk->makeDirectory($targetDir);
                    if ($disk->exists($new)) {
                        $disk->delete($new);
                    }
                    $disk->move($old, $new);
                    $employee->forceFill(['photo' => $new])->saveQuietly();
                }

                $moved++;
            });

        $this->info(($dryRun ? 'Simulados' : 'Migrados').": {$moved} archivo(s).");

        return self::SUCCESS;
    }
}
