<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\ActivityLogController;
use App\Http\Controllers\Web\AlertController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Web\ProjectMemberController;
use App\Http\Controllers\Web\RagDocumentController;
use App\Http\Controllers\Web\RagQueryController;
use App\Http\Controllers\Web\TaskController;
use App\Http\Controllers\Web\TaskDependencyController;
use App\Http\Controllers\Web\TaskProgressController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/export', [DashboardController::class, 'export'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.export');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('employees/export', [EmployeeController::class, 'exportCsv'])->name('employees.export');
    Route::resource('employees', EmployeeController::class)->except(['create', 'edit']);
    Route::get('projects/export', [ProjectController::class, 'exportCsv'])->name('projects.export');
    Route::get('projects/{project}/export', [ProjectController::class, 'export'])->name('projects.export.one');
    Route::resource('projects', ProjectController::class)->except(['create', 'edit']);
    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
    Route::delete('projects/{project}/members/{member}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');
    Route::get('tasks/export', [TaskController::class, 'exportCsv'])->name('tasks.export');
    Route::get('tasks/live-status', [TaskController::class, 'liveStatus'])->name('tasks.live-status');
    Route::get('tasks/{task}/live-status', [TaskController::class, 'liveStatusOne'])->name('tasks.live-status.one');
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status.update');
    Route::resource('tasks', TaskController::class)->except(['create', 'edit']);
    Route::post('tasks/{task}/dependencies', [TaskDependencyController::class, 'store'])->name('tasks.dependencies.store');
    Route::delete('tasks/{task}/dependencies/{dependency}', [TaskDependencyController::class, 'destroy'])->name('tasks.dependencies.destroy');
    Route::post('tasks/{task}/progress', [TaskProgressController::class, 'store'])->name('tasks.progress.store');

    Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('alerts/unread-count', [AlertController::class, 'unreadCount'])->name('alerts.unread-count');
    Route::post('alerts/read-all', [AlertController::class, 'markAllAsRead'])->name('alerts.read-all');
    Route::post('alerts/{notification}/read', [AlertController::class, 'markAsRead'])->name('alerts.read');

    Route::get('rag/documents', [RagDocumentController::class, 'index'])->name('rag-documents.index');
    Route::post('rag/documents', [RagDocumentController::class, 'store'])->name('rag-documents.store');
    Route::delete('rag/documents/{ragDocument}', [RagDocumentController::class, 'destroy'])->name('rag-documents.destroy');
    Route::post('rag/documents/{ragDocument}/retry', [RagDocumentController::class, 'retry'])->name('rag-documents.retry');

    Route::get('rag/chat', [RagQueryController::class, 'index'])->name('rag-query.index');
    Route::post('rag/query', [RagQueryController::class, 'store'])->name('rag-query.store');
    Route::get('rag/conversations/{conversation}', [RagQueryController::class, 'showConversation'])->name('rag-query.conversations.show');
    Route::delete('rag/conversations/{conversation}', [RagQueryController::class, 'destroyConversation'])->name('rag-query.conversations.destroy');
});

require __DIR__.'/auth.php';
