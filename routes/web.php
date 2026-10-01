<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\ActivityLogController;
use App\Http\Controllers\Web\AlertController;
use App\Http\Controllers\Web\AreaController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\CompanySignatureController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Web\ProjectMemberController;
use App\Http\Controllers\Web\RagDocumentController;
use App\Http\Controllers\Web\RagQueryController;
use App\Http\Controllers\Web\SecurityPolicyController;
use App\Http\Controllers\Web\TaskController;
use App\Http\Controllers\Web\TaskEvidenceController;
use App\Http\Controllers\Web\TaskStatusController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'throttle:web'])->group(function () {
    Route::get('employees/export', [EmployeeController::class, 'exportCsv'])->name('employees.export')->middleware('throttle:exports');
    Route::resource('employees', EmployeeController::class)->except(['create', 'edit']);
    Route::get('employees/{employee}/firma', [EmployeeController::class, 'firma'])->name('employees.firma');
    Route::get('employees/{employee}/contract', [EmployeeController::class, 'contract'])->name('employees.contract')->middleware('throttle:exports');
    Route::post('employees/{employee}/breach-notice', [EmployeeController::class, 'breachNotice'])->name('employees.breach-notice')->middleware('throttle:exports');

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
