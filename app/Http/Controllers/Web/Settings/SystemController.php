<?php

namespace App\Http\Controllers\Web\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRagDocument;
use App\Models\RagDocument;
use App\Services\Assistant\GroqGateway;
use App\Services\BackupService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SystemController extends Controller
{
    public function index()
    {
        return view('settings.system', [
            'documents' => RagDocument::select('estado')->selectRaw('COUNT(*) as total')->groupBy('estado')->get()->mapWithKeys(fn ($r) => [$r->estado->value => $r->total]),
            'failedDocuments' => RagDocument::where('estado', 'failed')->latest()->limit(10)->get(),
            'pendingJobs' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
            'failedJobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            'queueDriver' => config('queue.default'),
            'heartbeat' => Cache::get('workspace-scheduler-heartbeat'),
            'backup' => Cache::get('workspace-last-backup'),
            'backups' => collect(Storage::disk('local')->files('backups'))->filter(fn ($p) => str_ends_with($p, '.zip'))->sortDesc()->take(10),
            'aiConfigured' => app(GroqGateway::class)->enabled(),
            'models' => collect(config('assistant.models'))->map(fn ($model) => ['name' => $model, 'allowed' => in_array($model, config('assistant.allowed_free_models')), 'state' => Cache::get('assistant-model-status-'.md5($model))]),
        ]);
    }

    public function backup(Request $request, BackupService $service)
    {
        $request->validate(['admin_password' => ['required', 'current_password']]);
        try {
            $name = $service->create();
            $service->read($name);
            Cache::forever('workspace-last-backup', ['at' => now()->toIso8601String(), 'file' => $name, 'verified' => true]);
            ActivityLogger::record($request->user(), 'backup_created', 'Generó y verificó un respaldo cifrado.');

            return back()->with('status', 'Respaldo cifrado creado y verificado. Conserva una copia de APP_KEY de forma privada y separada para poder recuperarlo.');
        } catch (\Throwable $e) {
            return back()->withErrors(['backup' => 'No se pudo completar el respaldo. Revisa el espacio disponible, la extensión ZIP y el registro privado.']);
        }
    }

    public function download(Request $request)
    {
        $data = $request->validate(['admin_password' => ['required', 'current_password'], 'backup' => ['required', 'regex:/^backups\/backup-[A-Za-z0-9-]+\.zip$/D']]);
        abort_unless(Storage::disk('local')->exists($data['backup']), 404);

        return Storage::disk('local')->download($data['backup'], basename($data['backup']), ['Cache-Control' => 'private, no-store']);
    }

    public function retryDocuments()
    {
        RagDocument::where('estado', 'failed')->limit(10)->get()->each(function ($document) {
            $document->update(['estado' => 'pending', 'motivo_fallo' => null]);
            ProcessRagDocument::dispatch($document->id);
        });

        return back()->with('status','Hasta 10 documentos fallidos se enviaron nuevamente a procesamiento.');
    }
}
