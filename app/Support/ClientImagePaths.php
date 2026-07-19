<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Employee;
use Illuminate\Support\Facades\Storage;

class ClientImagePaths
{
    public static function logoDirectory(string $clientCode): string
    {
        return 'img/'.trim($clientCode).'/logo';
    }

    public static function employeesDirectory(string $clientCode): string
    {
        return 'img/'.trim($clientCode).'/employees';
    }

    public static function employeeDirectory(string $clientCode, string $employeeId): string
    {
        return self::employeesDirectory($clientCode).'/'.trim($employeeId);
    }

    public static function publicUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return request()->getSchemeAndHttpHost().'/storage/'.ltrim($path, '/');
    }

    /**
     * @return list<array{path: string, url: string, label: string, is_current: bool}>
     */
    public static function listBusinessImages(Client $client): array
    {
        $disk = Storage::disk('public');
        $images = [];
        $seen = [];
        $current = filled($client->logo) ? (string) $client->logo : null;

        $push = function (?string $path) use (&$images, &$seen, $disk, $current): void {
            if (! filled($path) || isset($seen[$path]) || ! $disk->exists($path)) {
                return;
            }

            $seen[$path] = true;
            $images[] = [
                'path' => $path,
                'url' => self::publicUrl($path) ?? '',
                'label' => __('panel.client_images.types.logo'),
                'is_current' => $current !== null && $path === $current,
            ];
        };

        $push($current);

        foreach ($disk->files(self::logoDirectory($client->code)) as $path) {
            $push($path);
        }

        usort($images, fn (array $a, array $b) => ((int) $b['is_current']) <=> ((int) $a['is_current']));

        return $images;
    }

    /**
     * @return list<array{
     *     employee: Employee,
     *     current_url: ?string,
     *     count: int,
     *     images: list<array{path: string, url: string, label: string, is_current: bool}>
     * }>
     */
    public static function listEmployeeFolders(Client $client): array
    {
        $client->loadMissing(['employees' => fn ($q) => $q->orderBy('name')]);
        $folders = [];

        foreach ($client->employees as $employee) {
            $images = self::listImagesForEmployee($client, $employee);
            if ($images === []) {
                continue;
            }

            $current = collect($images)->firstWhere('is_current') ?? $images[0];

            $folders[] = [
                'employee' => $employee,
                'current_url' => $current['url'] ?? null,
                'count' => count($images),
                'images' => $images,
            ];
        }

        return $folders;
    }

    /**
     * @return list<array{path: string, url: string, label: string, is_current: bool}>
     */
    public static function listImagesForEmployee(Client $client, Employee $employee): array
    {
        $disk = Storage::disk('public');
        $images = [];
        $seen = [];
        $current = filled($employee->photo) ? (string) $employee->photo : null;
        $label = $employee->name ?: __('panel.client_images.types.employee');

        $push = function (?string $path) use (&$images, &$seen, $disk, $current, $label): void {
            if (! filled($path) || isset($seen[$path]) || ! $disk->exists($path)) {
                return;
            }

            $seen[$path] = true;
            $images[] = [
                'path' => $path,
                'url' => self::publicUrl($path) ?? '',
                'label' => $label,
                'is_current' => $current !== null && $path === $current,
            ];
        };

        $push($current);

        $employeeDir = self::employeeDirectory($client->code, (string) $employee->id);
        foreach ($disk->files($employeeDir) as $path) {
            $push($path);
        }

        // Compatibilidad: fotos ya migradas a employees/ sin subcarpeta del empleado.
        if ($current && str_starts_with($current, self::employeesDirectory($client->code).'/')) {
            $relative = substr($current, strlen(self::employeesDirectory($client->code).'/'));
            if (! str_contains($relative, '/')) {
                $push($current);
            }
        }

        usort($images, fn (array $a, array $b) => ((int) $b['is_current']) <=> ((int) $a['is_current']));

        return $images;
    }

    /**
     * Cuenta total de archivos visibles para un cliente (negocio + empleados).
     */
    public static function countForClient(Client $client): int
    {
        $count = count(self::listBusinessImages($client));

        foreach (self::listEmployeeFolders($client) as $folder) {
            $count += $folder['count'];
        }

        return $count;
    }

    /**
     * @deprecated Usa listBusinessImages / listEmployeeFolders
     *
     * @return list<array{path: string, url: string, type: string, label: string}>
     */
    public static function listForClient(Client $client): array
    {
        $items = [];

        foreach (self::listBusinessImages($client) as $image) {
            $items[] = [
                'path' => $image['path'],
                'url' => $image['url'],
                'type' => 'logo',
                'label' => $image['label'],
            ];
        }

        foreach (self::listEmployeeFolders($client) as $folder) {
            foreach ($folder['images'] as $image) {
                $items[] = [
                    'path' => $image['path'],
                    'url' => $image['url'],
                    'type' => 'employee',
                    'label' => $image['label'],
                ];
            }
        }

        return $items;
    }

    /**
     * Mueve la foto actual del empleado a su carpeta si aún no está ahí.
     */
    public static function ensureEmployeePhotoInFolder(Employee $employee): void
    {
        $employee->loadMissing('client:id,code');

        if (! filled($employee->photo) || ! $employee->client?->code) {
            return;
        }

        $disk = Storage::disk('public');
        $old = (string) $employee->photo;
        $targetDir = self::employeeDirectory($employee->client->code, (string) $employee->id);
        $new = $targetDir.'/'.basename($old);

        if ($old === $new || ! $disk->exists($old)) {
            return;
        }

        $disk->makeDirectory($targetDir);

        if ($disk->exists($new) && $new !== $old) {
            $disk->delete($new);
        }

        $disk->move($old, $new);
        $employee->forceFill(['photo' => $new])->saveQuietly();
    }
}
