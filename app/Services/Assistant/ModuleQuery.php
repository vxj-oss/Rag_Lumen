<?php

namespace App\Services\Assistant;

use App\Models\Area;
use App\Models\Client;
use App\Models\EmployeeSanction;
use App\Models\TerminosReferencia;
use App\Models\User;
use App\Services\WorkspaceAttentionService;
use App\Support\WorkspaceAccess;
use App\Support\WorkspaceScope;
use Illuminate\Support\Str;

class ModuleQuery
{
    public function answer(string $question, User $user): ?array
    {
        $q = Str::lower(Str::ascii($question));
        if (preg_match('/\b(actividades|tareas|documentos|empleados|proyectos)\b/', $q) && ! preg_match('/\b(tdr|sanciones|contrato)\b/', $q)) {
            return null;
        }
        $rows = null;
        $label = '';
        $sources = [];
        if (preg_match('/\b(clientes?|usuarios?|areas|alertas|notificaciones|tdr|sanciones|contrato)\b/', $q, $m)) {
            $entity = $m[0];
            if (in_array($entity, ['cliente', 'clientes', 'usuario', 'usuarios']) && ! $user->isAdmin() && ! $user->isManager()) {
                return ['answer' => 'Tu rol no tiene acceso a ese módulo. Puedes consultar tus actividades, documentos, área y expediente.', 'sources' => []];
            }
            if (in_array($entity, ['cliente', 'clientes'])) {
                $label = 'clientes';
                $query = Client::query();
                if (str_contains($q, 'inactivos')) {
                    $query->where('estado', 'inactive');
                } elseif (str_contains($q, 'activos')) {
                    $query->where('estado', 'active');
                } $total = $query->count();
                $rows = $query->orderBy('nombre')->limit(20)->get()->map(fn ($r) => $r->nombre);
                $sources[] = ['title' => 'Clientes', 'url' => route('clients.index')];
            } elseif (in_array($entity, ['usuario', 'usuarios'])) {
                $label = 'usuarios';
                $query = User::with('roles');
                if (str_contains($q, 'inactivos')) {
                    $query->where('is_active', false);
                } elseif (str_contains($q, 'activos')) {
                    $query->where('is_active', true);
                } $total = $query->count();
                $rows = $query->orderBy('name')->limit(20)->get()->map(fn ($r) => $r->name.' · '.$r->roles->pluck('name')->join(', ').' · '.($r->is_active ? 'Activo' : 'Inactivo'));
            } elseif ($entity === 'areas') {
                $label = 'áreas registradas accesibles';
                $ids = WorkspaceAccess::areaIdsFor($user);
                $query = Area::query()->when($ids !== null, fn ($a) => $a->whereIn('id', $ids));
                $total = $query->count();
                $rows = $query->orderBy('nombre')->limit(20)->pluck('nombre');
                if ($user->isEmployee() && ! $total) {
                    $rows = collect(WorkspaceAccess::areasFor($user))->values();
                    $total = $rows->count();
                    $label = 'áreas de trabajo asignadas';
                }
            } elseif ($entity === 'contrato') {
                $employee = $user->employee;

                return ['answer' => $employee ? 'Tu convenio de colaboración está disponible en tu expediente personal. Nombre: '.$employee->fullName().'. Cargo: '.($employee->cargo ?? 'Sin registrar').'. Consulta el documento para revisar sus cláusulas.' : 'Tu cuenta no tiene un trabajador vinculado para consultar su contrato.', 'sources' => $employee ? [['title' => 'Consultar mi contrato', 'url' => route('employees.contract', $employee)]] : []];
            } elseif ($entity === 'tdr') {
                $label = 'TDR accesibles';
                $rows = collect();
                foreach (WorkspaceAccess::areasFor($user) as $area => $name) {
                    $tdr = TerminosReferencia::porArea($area);
                    if ($tdr) {
                        $rows->push($name.' · '.$tdr->nombre.' · Versión '.$tdr->version.((preg_match('/explica|contenido|resume|dice/', $q)) ? '\n'.Str::limit($tdr->contenido, 1400) : ''));
                    }
                } $total = $rows->count();
                $sources[] = ['title' => 'TDR y normativa', 'url' => route('normativa.index')];
                if ($user->employee) {
                    $sources[] = ['title' => 'Mi contrato y sanciones', 'url' => route('employees.record')];
                }
            } elseif ($entity === 'sanciones') {
                $label = 'sanciones registradas';
                $query = EmployeeSanction::whereIn('empleado_id', WorkspaceScope::employees($user)->select('id'))->with('employee');
                $total = $query->count();
                $records = $query->latest()->limit(20)->get();
                $rows = $records->map(fn ($r) => $r->employee->fullName().' · '.$r->fecha_hechos->format('d/m/Y').' · '.Str::limit($r->hechos, 300));
                foreach ($records as $r) {
                    $sources[] = ['title' => 'Acta '.$r->id, 'url' => route('sanctions.download', $r)];
                }
            } else {
                if ($user->isEmployee()) {
                    return ['answer' => 'Consulta el estado de tus actividades o tu expediente personal.', 'sources' => []];
                } $attention = count(app(WorkspaceAttentionService::class)->forUser($user));
                $label = 'notificaciones sin leer (además hay '.$attention.' situaciones activas de atención)';
                $total = $user->unreadNotifications()->count();
                $rows = $user->unreadNotifications()->limit(20)->get()->map(fn ($n) => $n->data['message'] ?? 'Notificación');
                $sources[] = ['title' => 'Alertas y situaciones actuales', 'url' => route('alerts.index')];
            }

            return ['answer' => 'Resultado verificado: '.$total.' '.$label.".\n".$rows->map(fn ($r) => '• '.$r)->join("\n").($total > 20 ? "\nSe muestran los primeros 20 registros." : '')."\nConsultado: ".now()->format('d/m/Y H:i'), 'sources' => $sources];
        }

        return null;
    }
}
