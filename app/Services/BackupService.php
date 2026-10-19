<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Throwable;
use ZipArchive;

class BackupService
{
    private const EXCLUDED = ['sessions', 'password_reset_tokens', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'migrations'];

    private const MAX_BYTES = 134217728;

    public function create(): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Se requiere la extensión ZIP de PHP.');
        }
        $disk = Storage::disk('local');
        $name = 'backups/backup-'.now()->format('Ymd-His').'-'.Str::random(8).'.zip';
        $disk->makeDirectory('backups');
        $zip = new ZipArchive;
        if ($zip->open($disk->path($name), ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('No se pudo crear el respaldo privado.');
        }
        $manifest = ['version' => 1, 'at' => now()->toIso8601String(), 'tables' => [], 'files' => []];
        $bytes = 0;
        try {
            DB::transaction(function () use (&$manifest, &$bytes, $zip) {
                foreach (Schema::getTableListing(null, false) as $table) {
                    if (in_array($table, self::EXCLUDED, true)) {
                        continue;
                    }
                    $columns = Schema::getColumnListing($table);
                    $chunks = [];
                    DB::table($table)->orderBy($columns[0])->chunk(500, function ($rows) use ($table, $zip, &$chunks, &$bytes) {
                        $json = json_encode($rows->all(), JSON_THROW_ON_ERROR);
                        $bytes += strlen($json);
                        if ($bytes > self::MAX_BYTES) {
                            throw new RuntimeException('El respaldo supera 128 MB. Se requiere un respaldo de servidor para este volumen.');
                        }
                        $entry = 'data/'.hash('sha256', $table).'-'.count($chunks).'.enc';
                        $encrypted = Crypt::encryptString($json);
                        if (! $zip->addFromString($entry, $encrypted)) {
                            throw new RuntimeException('No se pudo escribir el respaldo.');
                        }
                        $chunks[] = $entry;
                    });
                    $manifest['tables'][$table] = ['columns' => $columns, 'chunks' => $chunks];
                }
            });
            foreach (['local', 'public'] as $diskName) {
                $source = Storage::disk($diskName);
                foreach ($source->allFiles() as $path) {
                    if (str_starts_with($path, 'backups/') || str_ends_with($path, '.gitignore')) {
                        continue;
                    }
                    $size = $source->size($path);
                    $bytes += $size;
                    if ($size > 33554432 || $bytes > self::MAX_BYTES) {
                        throw new RuntimeException('El respaldo supera el tamaño permitido (32 MB por archivo, 128 MB en total).');
                    }
                    $content = $source->get($path);
                    $entry = 'files/'.hash('sha256', $diskName.'/'.$path).'.enc';
                    if (! $zip->addFromString($entry, Crypt::encryptString($content))) {
                        throw new RuntimeException('No se pudo respaldar un archivo.');
                    }
                    $manifest['files'][] = ['disk' => $diskName, 'path' => $path, 'entry' => $entry, 'hash' => hash('sha256', $content)];
                }
            }
            $zip->addFromString('manifest.enc', Crypt::encryptString(json_encode($manifest, JSON_THROW_ON_ERROR)));
            if (! $zip->close()) {
                throw new RuntimeException('No se pudo finalizar el respaldo.');
            }

            return $name;
        } catch (Throwable $e) {
            @$zip->close();
            $disk->delete($name);
            throw $e;
        }
    }

    public function read(string $name): array
    {
        if (! preg_match('/^backups\/backup-[A-Za-z0-9-]+\.zip$/D', $name)) {
            throw new RuntimeException('Nombre de respaldo no válido.');
        }
        $disk = Storage::disk('local');
        if (! $disk->exists($name) || $disk->size($name) > self::MAX_BYTES * 3) {
            throw new RuntimeException('Respaldo inexistente o demasiado grande.');
        }
        $zip = new ZipArchive;
        if ($zip->open($disk->path($name)) !== true) {
            throw new RuntimeException('No se pudo abrir el respaldo.');
        }
        try {
            $manifest = json_decode(Crypt::decryptString($zip->getFromName('manifest.enc')), true, 64, JSON_THROW_ON_ERROR);
            if (($manifest['version'] ?? null) !== 1) {
                throw new RuntimeException('Formato de respaldo no compatible.');
            }
            $expected = array_values(array_diff(Schema::getTableListing(null, false), self::EXCLUDED));
            $actual = array_keys($manifest['tables']);
            sort($expected);
            sort($actual);
            if ($expected !== $actual) {
                throw new RuntimeException('Las tablas del sistema cambiaron. Usa la misma versión y migraciones del respaldo.');
            }
            $tables = [];
            $files = [];
            $bytes = 0;
            $decode = function ($entry) use ($zip, &$bytes) {
                $stat = $zip->statName($entry);
                if (! $stat || $stat['size'] > 67108864) {
                    throw new RuntimeException('Entrada de respaldo no válida.');
                }
                $plain = Crypt::decryptString($zip->getFromName($entry));
                $bytes += strlen($plain);
                if ($bytes > self::MAX_BYTES) {
                    throw new RuntimeException('El contenido excede el límite de restauración.');
                }

                return $plain;
            };
            foreach ($manifest['tables'] as $table => $meta) {
                if (in_array($table, self::EXCLUDED, true) || ! Schema::hasTable($table) || Schema::getColumnListing($table) !== $meta['columns']) {
                    throw new RuntimeException('El esquema cambió. Restaura con la misma versión del sistema y sus migraciones.');
                }
                $tables[$table] = [];
                foreach ($meta['chunks'] as $entry) {
                    $tables[$table] = [...$tables[$table], ...json_decode($decode($entry), true, 512, JSON_THROW_ON_ERROR)];
                }
            }
            foreach ($manifest['files'] as $meta) {
                if (! in_array($meta['disk'], ['local', 'public']) || str_contains($meta['path'], '..') || str_contains($meta['path'], '\\') || str_contains($meta['path'], ':') || str_starts_with($meta['path'], '/') || str_starts_with($meta['path'], 'backups/')) {
                    throw new RuntimeException('Ruta de archivo no válida en el respaldo.');
                }
                $plain = $decode($meta['entry']);
                if (! hash_equals($meta['hash'], hash('sha256', $plain))) {
                    throw new RuntimeException('El respaldo no pasó la verificación de integridad.');
                }
                $files[] = [...$meta, 'content' => $plain];
            }

            return ['manifest' => $manifest, 'tables' => $tables, 'files' => $files];
        } finally {
            $zip->close();
        }
    }

    public function restore(string $name): void
    {
        $snapshot = $this->read($name);
        $originals = [];
        try {
            Schema::withoutForeignKeyConstraints(function () use ($snapshot, &$originals) {
                DB::transaction(function () use ($snapshot, &$originals) {
                    foreach ($snapshot['tables'] as $table => $rows) {
                        DB::table($table)->delete();
                        foreach (array_chunk($rows, 100) as $chunk) {
                            DB::table($table)->insert($chunk);
                        }
                    }
                    foreach ($snapshot['files'] as $file) {
                        $disk = Storage::disk($file['disk']);
                        $originals[] = ['disk' => $file['disk'], 'path' => $file['path'], 'content' => $disk->exists($file['path']) ? $disk->get($file['path']) : null];
                        if (! $disk->put($file['path'], $file['content'])) {
                            throw new RuntimeException('No se pudo restaurar un archivo.');
                        }
                    }
                    DB::table('users')->increment('auth_version');
                    DB::table('users')->update(['remember_token' => null]);
                    if (Schema::hasTable('sessions')) {
                        DB::table('sessions')->delete();
                    }
                });
            });
        } catch (Throwable $e) {
            foreach (array_reverse($originals) as $file) {
                $disk = Storage::disk($file['disk']);
                if ($file['content'] === null) {
                    $disk->delete($file['path']);
                } else {
                    $disk->put($file['path'], $file['content']);
                }
            }
            throw $e;
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
