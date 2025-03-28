<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryCostController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CollaborationController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});
Route::get('/register', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::resource('categories', CategoryController::class)
    ->only(['index', 'store', 'update', 'destroy', 'destroyCost'])
    ->middleware(['auth', 'verified', 'can:manage-categories']);

Route::resource('categoriesCost', CategoryCostController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware(['auth', 'verified', 'can:manage-categories']);

Route::resource('sales', SaleController::class)
    ->only(['index', 'store', 'update', 'destroy', 'show'])
    ->middleware(['auth', 'verified', 'can:manage-sales']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('clients')->group(function () {
        Route::get('/search', [ClientController::class, 'search'])->name('clients.search');
        Route::get('/trials', [ClientController::class, 'trials'])->name('clients.trials');
        Route::get('/old', [ClientController::class, 'old'])->name('clients.old');
        Route::get('/source-options', [ClientController::class, 'getSourceOptions'])->name('clients.getSourceOptions');

        Route::post('/store', [ClientController::class, 'store'])->name('clients.store');

        Route::resource('/', ClientController::class)
            ->only(['index', 'update', 'destroy', 'show'])
            ->names([
                'index' => 'clients.index', 'update' => 'clients.update',
                'destroy' => 'clients.destroy', 'show' => 'clients.show',
            ])
            ->parameters(['' => 'client']);
    });
    Route::prefix('tasks')->group(function () {
        Route::get('/no-show-leads', [TaskController::class, 'noShowLeads'])->name('tasks.noShowLeads');
        Route::get('/renewals', [TaskController::class, 'renewals'])->name('tasks.renewals');
        Route::get('/trials-month', [TaskController::class, 'trialsLessThanMonth'])->name('tasks.trialsLessThanMonth');
    });

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/export', [ExportController::class, 'index'])->name('export.index');
    Route::post('/export', [ExportController::class, 'export'])->name('export');

    Route::patch('/leads/{lead}/toggle-check', [LeadController::class, 'toggleCheck']);

    Route::prefix('collaboration')->group(function () {
        Route::get('/send-request', [CollaborationController::class, 'indexSendRequest'])
            ->name('collaboration.send-request');

        Route::get('/get-current-request/{id}', [CollaborationController::class, 'getCurrentRequest'])
            ->name('collaboration.getCurrentRequest');

        Route::get('/all-requests', [CollaborationController::class, 'index'])
            ->name('collaboration.all-requests');

        Route::post('/approve-request/{requestId}', [CollaborationController::class, 'approveRequest'])
            ->name('collaboration.approve-request');
        Route::post('/reject-request/{requestId}', [CollaborationController::class, 'rejectRequest'])
            ->name('collaboration.reject-request');
        Route::post('/delete-manager/{managerId}', [CollaborationController::class, 'deleteManager'])
            ->name('collaboration.delete-manager');

        Route::post('/send-request', [CollaborationController::class, 'sendRequest'])
            ->name('collaboration.send-request');

        Route::post('/delete-request/{id}', [CollaborationController::class, 'deleteRequest'])
            ->name('collaboration.delete-request');
    });

    Route::get('/admin/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

Route::resource('leads', LeadController::class)
    ->only(['index', 'store', 'update', 'destroy', 'show'])
    ->middleware(['auth', 'verified', 'can:manage-leads']);

Route::resource('tasks', TaskController::class)
    ->only(['index', 'store', 'update', 'destroy', 'show'])
    ->middleware(['auth', 'verified', 'can:manage-tasks']);

require __DIR__ . '/auth.php';
