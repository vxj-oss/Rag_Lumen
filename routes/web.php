<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\Activity\ActivityLogController;
use App\Http\Controllers\Web\Alerts\AlertController;
use App\Http\Controllers\Web\Areas\AreaController;
use App\Http\Controllers\Web\Clients\ClientController;
use App\Http\Controllers\Web\Settings\CompanySignatureController;
use App\Http\Controllers\Web\Dashboard\DashboardController;
use App\Http\Controllers\Web\Employees\EmployeeController;
use App\Http\Controllers\Web\Projects\ProjectController;
use App\Http\Controllers\Web\Projects\ProjectMemberController;
use App\Http\Controllers\Web\Rag\RagDocumentController;
use App\Http\Controllers\Web\Rag\RagQueryController;
use App\Http\Controllers\Web\Legal\SecurityPolicyController;
use App\Http\Controllers\Web\Normativa\NormativaController;
use App\Http\Controllers\Web\Tasks\TaskController;
use App\Http\Controllers\Web\Workspaces\WorkspaceController;
use App\Http\Controllers\Web\Tasks\TaskEvidenceController;
use App\Http\Controllers\Web\Tasks\TaskStatusController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::middleware(['auth', 'role:administrator', 'throttle:web'])->group(function () {
    Route::get('/system/archive', [\App\Http\Controllers\Web\Settings\ArchiveController::class,'index'])->name('archive.index');
    Route::post('/system/archive/{kind}/{id}/restore', [\App\Http\Controllers\Web\Settings\ArchiveController::class,'restore'])->name('archive.restore');
    Route::get('/system', [\App\Http\Controllers\Web\Settings\SystemController::class, 'index'])->name('system.index');
    Route::post('/system/backups', [\App\Http\Controllers\Web\Settings\SystemController::class, 'backup'])->middleware('throttle:2,1')->name('system.backup');
    Route::post('/system/backups/download', [\App\Http\Controllers\Web\Settings\SystemController::class, 'download'])->middleware('throttle:6,1')->name('system.download');
    Route::post('/system/documents/retry', [\App\Http\Controllers\Web\Settings\SystemController::class, 'retryDocuments'])->middleware('throttle:2,1')->name('system.retry');
    Route::get('/users', [\App\Http\Controllers\Web\Users\UserController::class, 'index'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\Web\Users\UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', fn (\App\Models\User $user) => view('users.edit', ['account' => $user->load('roles', 'employee'), 'roles' => \App\Support\Enums\RoleName::cases()]))->name('users.edit');
    Route::put('/users/{user}', [\App\Http\Controllers\Web\Users\UserController::class, 'update'])->name('users.update');
    Route::put('/users/{user}/password', [\App\Http\Controllers\Web\Users\UserController::class, 'password'])->middleware('throttle:6,1')->name('users.password');
});

Route::get('/workspace/search', \App\Http\Controllers\Web\Search\SearchController::class)
    ->middleware(['auth', 'throttle:60,1'])->name('workspace.search');

Route::get('/assistant/conversations', [RagQueryController::class, 'conversations'])->middleware(['auth','throttle:web'])->name('assistant.conversations');
Route::post('/tasks/{task}/comments',\App\Http\Controllers\Web\Tasks\TaskCommentController::class)->middleware(['auth','throttle:15,1'])->name('tasks.comments');
Route::put('/alerts/preferences', [AlertController::class,'preferences'])->middleware(['auth','throttle:web'])->name('alerts.preferences');
Route::post('/assistant/transcribe', \App\Http\Controllers\Web\Rag\VoiceController::class)->middleware(['auth','throttle:5,1'])->name('assistant.transcribe');

Route::get('/rag/documents/{ragDocument}/source', [RagDocumentController::class, 'source'])
    ->middleware(['auth', 'throttle:web'])->name('rag-documents.source');

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::get('/politicas-de-seguridad', [SecurityPolicyController::class, 'index'])
    ->name('legal.security-policy');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'throttle:web'])
    ->name('dashboard');

Route::get('/dashboard/export', [DashboardController::class, 'export'])
    ->middleware(['auth', 'verified', 'throttle:exports'])
    ->name('dashboard.export');

Route::middleware(['auth', 'throttle:web'])->group(function () {
    Route::get('my-record', [\App\Http\Controllers\Web\Employees\EmployeeRecordController::class, 'mine'])->name('employees.record');
    Route::get('sanctions/{sanction}/download', [\App\Http\Controllers\Web\Employees\EmployeeRecordController::class, 'download'])->name('sanctions.download');
    Route::put('sanctions/{sanction}', [\App\Http\Controllers\Web\Employees\EmployeeRecordController::class, 'update'])->name('sanctions.update');
    Route::get('employees/{employee}/access', [\App\Http\Controllers\Web\Employees\EmployeeAccessController::class, 'edit'])->name('employees.access.edit');
    Route::put('employees/{employee}/access', [\App\Http\Controllers\Web\Employees\EmployeeAccessController::class, 'update'])->middleware('throttle:6,1')->name('employees.access.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'throttle:web'])->group(function () {
    Route::get('employees/export', [EmployeeController::class, 'exportCsv'])->name('employees.export')->middleware('throttle:exports');
    Route::resource('employees', EmployeeController::class)->except(['create', 'edit']);
    Route::get('employees/{employee}/firma', [EmployeeController::class, 'firma'])->name('employees.firma');
    Route::get('employees/{employee}/normativa', [EmployeeController::class, 'normativa'])->name('employees.normativa')->middleware('throttle:exports');
    Route::post('employees/{employee}/normativa/aceptar', [EmployeeController::class, 'aceptarNormativa'])->name('employees.normativa.aceptar');
    Route::get('employees/{employee}/contract', [EmployeeController::class, 'contract'])->name('employees.contract')->middleware('throttle:exports');
    Route::post('employees/{employee}/breach-notice', [EmployeeController::class, 'breachNotice'])->name('employees.breach-notice')->middleware('throttle:exports');

    Route::get('espacios', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::get('espacios/{area}', [WorkspaceController::class, 'show'])->whereIn('area', ['software', 'web', 'marketing'])->name('workspaces.show');

    Route::get('normativa', [NormativaController::class, 'index'])->name('normativa.index');
Route::put('normativa/tdr/{tdr}', [NormativaController::class, 'updateTdr'])->middleware('role:administrator|manager')->name('normativa.tdr.update');
    Route::post('normativa/faltas', [NormativaController::class, 'storeFalta'])->middleware('role:administrator|manager|project_lead')->name('normativa.faltas.store');
    Route::put('normativa/faltas/{falta}', [NormativaController::class, 'updateFalta'])->middleware('role:administrator|manager|project_lead')->name('normativa.faltas.update');
    Route::delete('normativa/faltas/{falta}', [NormativaController::class, 'destroyFalta'])->middleware('role:administrator|manager|project_lead')->name('normativa.faltas.destroy');

    Route::middleware('role:administrator')->group(function () {


        Route::get('configuracion/firma-empresa', [CompanySignatureController::class, 'edit'])->name('settings.company-signature.edit');
        Route::post('configuracion/firma-empresa', [CompanySignatureController::class, 'update'])->name('settings.company-signature.update');
        Route::delete('configuracion/firma-empresa', [CompanySignatureController::class, 'destroy'])->name('settings.company-signature.destroy');
    });
    Route::get('clients/export', [ClientController::class, 'exportCsv'])->name('clients.export')->middleware('throttle:exports');
    Route::post('clients/quick', [ClientController::class, 'quickStore'])->name('clients.quick');
    Route::resource('clients', ClientController::class)->except(['create', 'edit']);
    Route::resource('areas', AreaController::class)->except(['create', 'edit', 'show']);
    Route::get('projects/export', [ProjectController::class, 'exportCsv'])->name('projects.export')->middleware('throttle:exports');
    Route::get('projects/{project}/export', [ProjectController::class, 'export'])->name('projects.export.one')->middleware('throttle:exports');
    Route::resource('projects', ProjectController::class)->except(['create', 'edit']);
    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
    Route::delete('projects/{project}/members/{member}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');
    Route::get('projects/{project}/scope-data', [ProjectController::class, 'scopeData'])->name('projects.scope-data');
    Route::get('projects/{project}/statuses', [TaskStatusController::class, 'index'])->name('projects.statuses.index');
    Route::post('projects/{project}/statuses', [TaskStatusController::class, 'store'])->name('projects.statuses.store');
    Route::put('projects/{project}/statuses/{status}', [TaskStatusController::class, 'update'])->name('projects.statuses.update');
    Route::delete('projects/{project}/statuses/{status}', [TaskStatusController::class, 'destroy'])->name('projects.statuses.destroy');
    Route::post('projects/{project}/statuses/reorder', [TaskStatusController::class, 'reorder'])->name('projects.statuses.reorder');
    Route::get('tasks/export', [TaskController::class, 'exportCsv'])->name('tasks.export')->middleware('throttle:exports');
    Route::get('tasks/board', [TaskController::class, 'board'])->name('tasks.board');
    Route::get('tasks/live-status', [TaskController::class, 'liveStatus'])->name('tasks.live-status');
    Route::get('tasks/{task}/live-status', [TaskController::class, 'liveStatusOne'])->name('tasks.live-status.one');
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status.update');
    Route::resource('tasks', TaskController::class)->except(['create', 'edit']);
    Route::post('tasks/{task}/tasks', [TaskController::class, 'storeChild'])->name('tasks.children.store');
    Route::post('tasks/{task}/evidence', [TaskEvidenceController::class, 'store'])->name('tasks.evidence.store')->middleware('throttle:rag-upload');
    Route::post('tasks/{task}/evidence/approve', [TaskEvidenceController::class, 'approve'])->name('tasks.evidence.approve');
    Route::post('tasks/{task}/evidence/reject', [TaskEvidenceController::class, 'reject'])->name('tasks.evidence.reject');
    Route::get('tasks/{task}/evidence/{evidence}/download', [TaskEvidenceController::class, 'download'])->name('tasks.evidence.download');

    Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('alerts/unread-count', [AlertController::class, 'unreadCount'])->name('alerts.unread-count');
    Route::post('alerts/read-all', [AlertController::class, 'markAllAsRead'])->name('alerts.read-all');
    Route::post('alerts/{notification}/read', [AlertController::class, 'markAsRead'])->name('alerts.read');

    Route::get('rag/documents', [RagDocumentController::class, 'index'])->name('rag-documents.index');
    Route::post('rag/documents', [RagDocumentController::class, 'store'])->name('rag-documents.store')->middleware('throttle:rag-upload');
    Route::delete('rag/documents/{ragDocument}', [RagDocumentController::class, 'destroy'])->name('rag-documents.destroy');
    Route::post('rag/documents/{ragDocument}/retry', [RagDocumentController::class, 'retry'])->name('rag-documents.retry');

    Route::get('rag/chat', [RagQueryController::class, 'index'])->name('rag-query.index');
    Route::post('rag/query', [RagQueryController::class, 'store'])->name('rag-query.store')->middleware('throttle:rag-query');
    Route::get('rag/conversations/{conversation}', [RagQueryController::class, 'showConversation'])->name('rag-query.conversations.show');
    Route::delete('rag/conversations/{conversation}', [RagQueryController::class, 'destroyConversation'])->name('rag-query.conversations.destroy');
});

require __DIR__.'/auth.php';
