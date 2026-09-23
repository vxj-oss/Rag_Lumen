<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\TaskProgressUpdate;
use App\Models\TaskState;
use App\Models\User;
use App\Services\ProjectRiskService;
use App\Support\Enums\EmployeeSpecialty;
use App\Support\Enums\EmployeeStatus;
use App\Support\Enums\Priority;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\ProjectType;
use App\Support\Enums\RoleName;
use App\Support\Enums\TaskStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(AreaSeeder::class);

        $now = now();

        $admin = $this->user('Admin Demo', 'admin.demo@example.com', RoleName::Administrator);
        $manager = $this->user('Gerente Demo', 'gerente.demo@example.com', RoleName::Manager);
        $lead = $this->user('Líder Demo', 'lider.demo@example.com', RoleName::ProjectLead);

        $employeeUsers = collect([
            ['Ana Torres', 'ana.torres@example.com', EmployeeSpecialty::Marketing, 'Estratega de Marketing'],
            ['Bruno Díaz', 'bruno.diaz@example.com', EmployeeSpecialty::Seo, 'Especialista SEO'],
            ['Carla Ruiz', 'carla.ruiz@example.com', EmployeeSpecialty::GraphicDesign, 'Diseñadora Gráfica'],
            ['Diego Paredes', 'diego.paredes@example.com', EmployeeSpecialty::Frontend, 'Desarrollador Frontend'],
            ['Elena Vargas', 'elena.vargas@example.com', EmployeeSpecialty::Backend, 'Desarrolladora Backend'],
            ['Felipe Mora', 'felipe.mora@example.com', EmployeeSpecialty::Advertising, 'Especialista en Publicidad'],
        ])->map(fn (array $row) => $this->employeeWithUser($row[0], $row[1], $row[2], $row[3], RoleName::Employee));

        $leadEmployee = $this->employeeFor($lead, 'Líder', 'Demo', EmployeeSpecialty::ProjectManager, 'Líder de Proyecto');
        $managerEmployee = $this->employeeFor($manager, 'Gerente', 'Demo', EmployeeSpecialty::Marketing, 'Gerente de Marketing');

        $areasByName = Area::pluck('id', 'nombre');

        $areaBySpecialty = [
            EmployeeSpecialty::Marketing->value => 'Contenido',
            EmployeeSpecialty::Seo->value => 'SEO/SEM',
            EmployeeSpecialty::GraphicDesign->value => 'Diseño',
            EmployeeSpecialty::Frontend->value => 'Diseño',
            EmployeeSpecialty::Backend->value => 'Estrategia',
            EmployeeSpecialty::Advertising->value => 'Redes sociales',
            EmployeeSpecialty::ProjectManager->value => 'Estrategia',
        ];

        foreach (Employee::all() as $employee) {
            $areaName = $areaBySpecialty[$employee->especialidad->value] ?? 'Estrategia';
            $employee->update(['area_id' => $areasByName[$areaName] ?? null]);
        }

        $clientsByName = [];
        foreach ([
            ['EnerPlus', 'Energía'],
            ['Corporación Andina', 'Industrial'],
            ['Tiendas Nativas', 'Retail'],
            ['Café Lumen', 'Gastronomía'],
            ['RetailMax', 'Retail'],
        ] as [$clientName, $sector]) {
            $clientsByName[$clientName] = Client::firstOrCreate(
                ['nombre' => $clientName],
                ['sector' => $sector, 'estado' => 'active']
            );
        }

        $allEmployees = Employee::whereIn('correo', array_merge(
            ['lider.demo@example.com', 'gerente.demo@example.com'],
            $employeeUsers->pluck('email')->all()
        ))->get()->keyBy('correo');

        // Proyecto sano: campaña en curso con buen avance.
        $campaign = $this->project([
            'code' => 'MKT-001',
            'name' => 'Campaña de Lanzamiento EnerPlus',
            'description' => 'Campaña digital de lanzamiento con pauta en redes, email marketing y landing page.',
            'type' => ProjectType::DigitalMarketing,
            'client_id' => $clientsByName['EnerPlus']->id,
            'start_date' => $now->copy()->subDays(40)->toDateString(),
            'estimated_end_date' => $now->copy()->addDays(20)->toDateString(),
            'status' => ProjectStatus::InProgress,
            'priority' => Priority::High,
            'responsible_employee_id' => $leadEmployee->id,
            'budget' => 18000.00,
        ]);

        // Proyecto en riesgo: tareas atrasadas + bloqueadas + fecha límite cercana.
        $website = $this->project([
            'code' => 'WEB-002',
            'name' => 'Rediseño del Sitio Corporativo Andina',
            'description' => 'Rediseño completo del sitio corporativo: UX, frontend, migración de contenidos y SEO técnico.',
            'type' => ProjectType::WebDevelopment,
            'client_id' => $clientsByName['Corporación Andina']->id,
            'start_date' => $now->copy()->subDays(70)->toDateString(),
            'estimated_end_date' => $now->copy()->addDays(5)->toDateString(),
            'status' => ProjectStatus::InProgress,
            'priority' => Priority::Critical,
            'responsible_employee_id' => $leadEmployee->id,
            'budget' => 32000.00,
        ]);

        // Proyecto completado: historial de éxito para comparar.
        $seo = $this->project([
            'code' => 'SEO-003',
            'name' => 'Auditoría y Posicionamiento SEO Trimestral',
            'description' => 'Auditoría técnica, optimización on-page y estrategia de contenidos del trimestre.',
            'type' => ProjectType::Seo,
            'client_id' => $clientsByName['Tiendas Nativas']->id,
            'start_date' => $now->copy()->subDays(100)->toDateString(),
            'estimated_end_date' => $now->copy()->subDays(10)->toDateString(),
            'actual_end_date' => $now->copy()->subDays(12)->toDateString(),
            'status' => ProjectStatus::Completed,
            'priority' => Priority::Medium,
            'responsible_employee_id' => $allEmployees['bruno.diaz@example.com']->id,
            'budget' => 9000.00,
        ]);

        // Proyecto en revisión y uno en planificación.
        $branding = $this->project([
            'code' => 'BRD-004',
            'name' => 'Identidad de Marca Café Lumen',
            'description' => 'Nueva identidad visual: logotipo, paleta, tipografías y manual de marca.',
            'type' => ProjectType::Branding,
            'client_id' => $clientsByName['Café Lumen']->id,
            'start_date' => $now->copy()->subDays(25)->toDateString(),
            'estimated_end_date' => $now->copy()->addDays(15)->toDateString(),
            'status' => ProjectStatus::Review,
            'priority' => Priority::Medium,
            'responsible_employee_id' => $allEmployees['carla.ruiz@example.com']->id,
            'budget' => 12000.00,
        ]);

        $ads = $this->project([
            'code' => 'ADV-005',
            'name' => 'Campaña Publicitaria Navidad RetailMax',
            'description' => 'Planificación de la campaña navideña multicanal: TV, digital y vía pública.',
            'type' => ProjectType::Campaign,
            'client_id' => $clientsByName['RetailMax']->id,
            'start_date' => $now->copy()->addDays(10)->toDateString(),
            'estimated_end_date' => $now->copy()->addDays(70)->toDateString(),
            'status' => ProjectStatus::Planning,
            'priority' => Priority::Medium,
            'responsible_employee_id' => $managerEmployee->id,
            'budget' => 45000.00,
        ]);

        $campaign->update([
            'cliente_id' => $clientsByName['EnerPlus']->id,
            'empleado_gerente_id' => $managerEmployee->id,
        ]);
        $campaign->areas()->sync([
            $areasByName['Redes sociales'],
            $areasByName['Contenido'],
            $areasByName['Diseño'],
        ]);

        $website->update([
            'cliente_id' => $clientsByName['Corporación Andina']->id,
            'empleado_gerente_id' => $managerEmployee->id,
        ]);
        $website->areas()->sync([
            $areasByName['Diseño'],
            $areasByName['SEO/SEM'],
            $areasByName['Estrategia'],
        ]);

        $seo->update(['cliente_id' => $clientsByName['Tiendas Nativas']->id]);
        $seo->areas()->sync([$areasByName['SEO/SEM'], $areasByName['Contenido']]);

        $branding->update(['cliente_id' => $clientsByName['Café Lumen']->id]);
        $branding->areas()->sync([$areasByName['Diseño']]);

        $ads->update([
            'cliente_id' => $clientsByName['RetailMax']->id,
            'empleado_gerente_id' => $managerEmployee->id,
        ]);
        $ads->areas()->sync([$areasByName['Redes sociales'], $areasByName['Estrategia']]);

        $this->attachMembers($campaign, [
            [$leadEmployee, 'Líder de Proyecto'],
            [$allEmployees['ana.torres@example.com'], 'Estratega'],
            [$allEmployees['felipe.mora@example.com'], 'Pauta Publicitaria'],
            [$allEmployees['diego.paredes@example.com'], 'Landing Page'],
        ], $now);

        $this->attachMembers($website, [
            [$leadEmployee, 'Líder de Proyecto'],
            [$allEmployees['diego.paredes@example.com'], 'Frontend'],
            [$allEmployees['elena.vargas@example.com'], 'Backend'],
            [$allEmployees['bruno.diaz@example.com'], 'SEO Técnico'],
        ], $now);

        $this->attachMembers($seo, [
            [$allEmployees['bruno.diaz@example.com'], 'Responsable SEO'],
            [$allEmployees['ana.torres@example.com'], 'Contenidos'],
        ], $now);

        $this->attachMembers($branding, [
            [$allEmployees['carla.ruiz@example.com'], 'Diseñadora Líder'],
            [$managerEmployee, 'Supervisión'],
        ], $now);

        $this->attachMembers($ads, [
            [$managerEmployee, 'Responsable'],
            [$allEmployees['felipe.mora@example.com'], 'Planificación de Medios'],
        ], $now);

        // --- Tareas MKT-001 (proyecto sano) ---
        $t1 = $this->task($campaign, 'Definir buyer persona y mensajes clave', $allEmployees['ana.torres@example.com']->id, $admin->id, [
            'status' => TaskStatus::Completed, 'priority' => Priority::High,
            'start_date' => $now->copy()->subDays(38)->toDateString(), 'due_date' => $now->copy()->subDays(30)->toDateString(),
            'progress_percentage' => 100, 'estimated_hours' => 12, 'actual_hours' => 10,
            'completed_at' => $now->copy()->subDays(30),
        ]);
        $t2 = $this->task($campaign, 'Diseñar landing page de lanzamiento', $allEmployees['diego.paredes@example.com']->id, $admin->id, [
            'status' => TaskStatus::InProgress, 'priority' => Priority::High,
            'start_date' => $now->copy()->subDays(20)->toDateString(), 'due_date' => $now->copy()->addDays(8)->toDateString(),
            'progress_percentage' => 65, 'estimated_hours' => 30,
        ]);
        $t3 = $this->task($campaign, 'Configurar pauta en Meta y Google Ads', $allEmployees['felipe.mora@example.com']->id, $admin->id, [
            'status' => TaskStatus::InProgress, 'priority' => Priority::Medium,
            'start_date' => $now->copy()->subDays(10)->toDateString(), 'due_date' => $now->copy()->addDays(12)->toDateString(),
            'progress_percentage' => 40, 'estimated_hours' => 20,
        ]);
        $t4 = $this->task($campaign, 'Secuencia de email marketing (3 envíos)', $allEmployees['ana.torres@example.com']->id, $admin->id, [
            'status' => TaskStatus::Pending, 'priority' => Priority::Medium,
            'start_date' => $now->copy()->addDays(5)->toDateString(), 'due_date' => $now->copy()->addDays(18)->toDateString(),
            'progress_percentage' => 0, 'estimated_hours' => 10,
        ]);
        $this->dependsOn($t2, $t1);
        $this->dependsOn($t4, $t2);

        // --- Tareas WEB-002 (proyecto en riesgo) ---
        $w1 = $this->task($website, 'Levantamiento de requerimientos y sitemap', $allEmployees['elena.vargas@example.com']->id, $admin->id, [
            'status' => TaskStatus::Completed, 'priority' => Priority::High,
            'start_date' => $now->copy()->subDays(68)->toDateString(), 'due_date' => $now->copy()->subDays(55)->toDateString(),
            'progress_percentage' => 100, 'estimated_hours' => 16, 'actual_hours' => 18,
            'completed_at' => $now->copy()->subDays(54),
        ]);
        $w2 = $this->task($website, 'Diseño UX/UI de plantillas principales', $allEmployees['diego.paredes@example.com']->id, $admin->id, [
            'status' => TaskStatus::InProgress, 'priority' => Priority::Critical,
            'start_date' => $now->copy()->subDays(40)->toDateString(), 'due_date' => $now->copy()->subDays(12)->toDateString(),
            'progress_percentage' => 55, 'estimated_hours' => 40,
        ]);
        $w3 = $this->task($website, 'Migración de contenidos legacy', $allEmployees['elena.vargas@example.com']->id, $admin->id, [
            'status' => TaskStatus::Blocked, 'priority' => Priority::High,
            'start_date' => $now->copy()->subDays(25)->toDateString(), 'due_date' => $now->copy()->subDays(5)->toDateString(),
            'progress_percentage' => 20, 'estimated_hours' => 24,
            'blocked_reason' => 'El cliente aún no entrega los contenidos finales ni accesos al CMS actual.',
        ]);
        $w4 = $this->task($website, 'Maquetación frontend responsive', $allEmployees['diego.paredes@example.com']->id, $admin->id, [
            'status' => TaskStatus::Pending, 'priority' => Priority::High,
            'start_date' => $now->copy()->subDays(5)->toDateString(), 'due_date' => $now->copy()->subDays(1)->toDateString(),
            'progress_percentage' => 0, 'estimated_hours' => 35,
        ]);
        $w5 = $this->task($website, 'SEO técnico y redirecciones 301', $allEmployees['bruno.diaz@example.com']->id, $admin->id, [
            'status' => TaskStatus::Pending, 'priority' => Priority::Medium,
            'start_date' => $now->copy()->toDateString(), 'due_date' => $now->copy()->addDays(4)->toDateString(),
            'progress_percentage' => 0, 'estimated_hours' => 12,
        ]);
        $this->dependsOn($w2, $w1);
        $this->dependsOn($w3, $w1);
        $this->dependsOn($w4, $w2);
        $this->dependsOn($w5, $w4);

        // --- Tareas SEO-003 (completado) ---
        $s1 = $this->task($seo, 'Auditoría técnica del sitio', $allEmployees['bruno.diaz@example.com']->id, $admin->id, [
            'status' => TaskStatus::Completed, 'priority' => Priority::High,
            'start_date' => $now->copy()->subDays(95)->toDateString(), 'due_date' => $now->copy()->subDays(80)->toDateString(),
            'progress_percentage' => 100, 'estimated_hours' => 20, 'actual_hours' => 22,
            'completed_at' => $now->copy()->subDays(80),
        ]);
        $s2 = $this->task($seo, 'Optimización on-page de 30 URLs', $allEmployees['bruno.diaz@example.com']->id, $admin->id, [
            'status' => TaskStatus::Completed, 'priority' => Priority::Medium,
            'start_date' => $now->copy()->subDays(75)->toDateString(), 'due_date' => $now->copy()->subDays(40)->toDateString(),
            'progress_percentage' => 100, 'estimated_hours' => 35, 'actual_hours' => 33,
            'completed_at' => $now->copy()->subDays(42),
        ]);
        $s3 = $this->task($seo, 'Calendario de contenidos del trimestre', $allEmployees['ana.torres@example.com']->id, $admin->id, [
            'status' => TaskStatus::Completed, 'priority' => Priority::Medium,
            'start_date' => $now->copy()->subDays(50)->toDateString(), 'due_date' => $now->copy()->subDays(15)->toDateString(),
            'progress_percentage' => 100, 'estimated_hours' => 15, 'actual_hours' => 14,
            'completed_at' => $now->copy()->subDays(16),
        ]);
        $this->dependsOn($s2, $s1);

        // --- Tareas BRD-004 (en revisión) ---
        $b1 = $this->task($branding, 'Propuestas de logotipo (3 rutas)', $allEmployees['carla.ruiz@example.com']->id, $admin->id, [
            'status' => TaskStatus::Review, 'priority' => Priority::High,
            'start_date' => $now->copy()->subDays(24)->toDateString(), 'due_date' => $now->copy()->addDays(2)->toDateString(),
            'progress_percentage' => 90, 'estimated_hours' => 25,
        ]);
        $b2 = $this->task($branding, 'Manual de marca v1', $allEmployees['carla.ruiz@example.com']->id, $admin->id, [
            'status' => TaskStatus::InProgress, 'priority' => Priority::Medium,
            'start_date' => $now->copy()->subDays(8)->toDateString(), 'due_date' => $now->copy()->addDays(14)->toDateString(),
            'progress_percentage' => 35, 'estimated_hours' => 18,
        ]);
        $this->dependsOn($b2, $b1);

        // --- Tareas ADV-005 (planificación) ---
        $this->task($ads, 'Brief y objetivos de campaña', $allEmployees['felipe.mora@example.com']->id, $admin->id, [
            'status' => TaskStatus::Pending, 'priority' => Priority::Medium,
            'start_date' => $now->copy()->addDays(10)->toDateString(), 'due_date' => $now->copy()->addDays(20)->toDateString(),
            'progress_percentage' => 0, 'estimated_hours' => 8,
        ]);
        $this->task($ads, 'Plan de medios multicanal', $allEmployees['felipe.mora@example.com']->id, $admin->id, [
            'status' => TaskStatus::Pending, 'priority' => Priority::Medium,
            'start_date' => $now->copy()->addDays(21)->toDateString(), 'due_date' => $now->copy()->addDays(35)->toDateString(),
            'progress_percentage' => 0, 'estimated_hours' => 16,
        ]);

        // Historial de avances coherente (previous -> new encadenados).
        $this->progressHistory($t1, $admin->id, [[0, 45, 'Levantamiento inicial de buyer persona.'], [45, 100, 'Mensajes validados con el cliente.']], $now->copy()->subDays(32));
        $this->progressHistory($t2, $admin->id, [[0, 30, 'Wireframes aprobados.'], [30, 65, 'Maquetación de hero y secciones principales.']], $now->copy()->subDays(9));
        $this->progressHistory($t3, $admin->id, [[0, 40, 'Cuentas y píxeles configurados.']], $now->copy()->subDays(4));
        $this->progressHistory($w2, $admin->id, [[0, 30, 'Primeras plantillas entregadas.'], [30, 55, 'Iteración con feedback del cliente.']], $now->copy()->subDays(15));
        $this->progressHistory($w3, $admin->id, [[0, 20, 'Inventario de URLs legacy listo. Bloqueado a la espera del cliente.']], $now->copy()->subDays(8));
        $this->progressHistory($b1, $admin->id, [[0, 60, 'Dos rutas presentadas.'], [60, 90, 'Tercera ruta en ajuste fino.']], $now->copy()->subDays(3));
        $this->progressHistory($s2, $admin->id, [[0, 50, 'Primera mitad de URLs optimizadas.'], [50, 100, 'Lote final publicado y verificado.']], $now->copy()->subDays(45));

        // Recalcula riesgo/métricas persistidos para que el dashboard muestre valores reales.
        $riskService = app(ProjectRiskService::class);

        foreach (Project::whereIn('codigo', ['MKT-001', 'WEB-002', 'SEO-003', 'BRD-004', 'ADV-005'])->get() as $project) {
            $riskService->recalculate($project);
        }
    }

    private function user(string $name, string $email, RoleName $role): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'password', 'email_verified_at' => now()]
        );

        if (! $user->hasRole($role->value)) {
            $user->assignRole($role->value);
        }

        return $user;
    }

    private function employeeWithUser(string $fullName, string $email, EmployeeSpecialty $specialty, string $position, RoleName $role): User
    {
        $user = $this->user($fullName, $email, $role);

        [$firstName, $lastName] = array_pad(explode(' ', $fullName, 2), 2, '');

        Employee::firstOrCreate(
            ['correo' => $email],
            [
                'usuario_id' => $user->id,
                'nombres' => $firstName,
                'apellidos' => $lastName ?: $firstName,
                'telefono' => '+591 7'.random_int(1000000, 7999999),
                'cargo' => $position,
                'especialidad' => $specialty->value,
                'estado' => EmployeeStatus::Active->value,
                'fecha_contratacion' => now()->subMonths(random_int(2, 24))->toDateString(),
            ]
        );

        // Si el empleado ya existía sin usuario vinculado, lo vincula.
        Employee::where('correo', $email)->whereNull('usuario_id')->update(['usuario_id' => $user->id]);

        return $user;
    }

    private function employeeFor(User $user, string $firstName, string $lastName, EmployeeSpecialty $specialty, string $position): Employee
    {
        $employee = Employee::firstOrCreate(
            ['correo' => $user->email],
            [
                'usuario_id' => $user->id,
                'nombres' => $firstName,
                'apellidos' => $lastName,
                'cargo' => $position,
                'especialidad' => $specialty->value,
                'estado' => EmployeeStatus::Active->value,
                'fecha_contratacion' => now()->subYear()->toDateString(),
            ]
        );

        if ($employee->usuario_id === null) {
            $employee->update(['usuario_id' => $user->id]);
        }

        return $employee->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function project(array $attributes): Project
    {
        $map = [
            'code' => 'codigo', 'name' => 'nombre', 'description' => 'descripcion', 'type' => 'tipo',
            'client_id' => 'cliente_id', 'start_date' => 'fecha_inicio', 'estimated_end_date' => 'fecha_fin_estimada',
            'actual_end_date' => 'fecha_fin_real', 'status' => 'estado', 'priority' => 'prioridad',
            'responsible_employee_id' => 'empleado_responsable_id', 'manager_employee_id' => 'empleado_gerente_id',
            'budget' => 'presupuesto', 'observations' => 'observaciones',
        ];

        $translated = collect($attributes)->mapWithKeys(fn (mixed $value, string $key) => [$map[$key] ?? $key => $value]);

        return Project::updateOrCreate(
            ['codigo' => $attributes['code']],
            $translated->except('codigo')->mapWithKeys(function (mixed $value, string $key) {
                if ($value instanceof \BackedEnum) {
                    return [$key => $value->value];
                }

                if ($value instanceof Carbon) {
                    return [$key => $value->toDateString()];
                }

                return [$key => $value];
            })->all()
        );
    }

    /**
     * @param  array<int, array{0: Employee, 1: string}>  $members
     */
    private function attachMembers(Project $project, array $members, Carbon $now): void
    {
        foreach ($members as [$employee, $role]) {
            ProjectMember::firstOrCreate(
                ['proyecto_id' => $project->id, 'empleado_id' => $employee->id],
                ['rol_en_proyecto' => $role, 'asignado_en' => $now->copy()->subDays(30)->toDateString(), 'estado' => 'active']
            );
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function task(Project $project, string $title, int $assigneeId, int $creatorId, array $attributes): Task
    {
        $map = [
            'status' => 'estado', 'priority' => 'prioridad', 'start_date' => 'fecha_inicio', 'due_date' => 'fecha_vencimiento',
            'progress_percentage' => 'porcentaje_progreso', 'estimated_hours' => 'horas_estimadas', 'actual_hours' => 'horas_reales',
            'completed_at' => 'completado_en', 'blocked_reason' => 'motivo_bloqueo',
        ];

        $attributes = collect($attributes)->mapWithKeys(fn (mixed $value, string $key) => [$map[$key] ?? $key => $value])->all();

        $payload = array_merge($attributes, [
            'proyecto_id' => $project->id,
            'asignado_a' => $assigneeId,
            'creado_por' => $creatorId,
            'titulo' => $title,
        ]);

        foreach (['estado', 'prioridad'] as $enumKey) {
            if (($payload[$enumKey] ?? null) instanceof \BackedEnum) {
                $payload[$enumKey] = $payload[$enumKey]->value;
            }
        }

        if (($payload['completado_en'] ?? null) instanceof Carbon) {
            $payload['completado_en'] = $payload['completado_en']->toDateTimeString();
        }

        $payload['estado_id'] = TaskState::whereNull('proyecto_id')
            ->where('slug', $this->slugForStatus($payload['estado'] ?? 'pending'))
            ->value('id');

        $task = Task::where('proyecto_id', $project->id)->where('titulo', $title)->first();

        if ($task) {
            $task->update($payload);

            return $task->refresh();
        }

        return Task::create($payload);
    }

    private function slugForStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'pendiente',
            'in_progress' => 'en-progreso',
            'blocked' => 'bloqueada',
            'review' => 'en-revision',
            'completed' => 'completada',
            'cancelled' => 'cancelada',
            default => 'pendiente',
        };
    }

    private function dependsOn(Task $task, Task $dependsOn): void
    {
        if ($task->id === $dependsOn->id) {
            return;
        }

        TaskDependency::firstOrCreate([
            'tarea_id' => $task->id,
            'depende_de_tarea_id' => $dependsOn->id,
        ]);
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: string}>  $steps
     */
    private function progressHistory(Task $task, int $userId, array $steps, Carbon $base): void
    {
        foreach ($steps as $i => [$previous, $new, $comment]) {
            TaskProgressUpdate::firstOrCreate(
                ['tarea_id' => $task->id, 'porcentaje_anterior' => $previous, 'porcentaje_nuevo' => $new],
                ['usuario_id' => $userId, 'comentario' => $comment, 'created_at' => $base->copy()->addHours($i * 5)]
            );
        }
    }
}
