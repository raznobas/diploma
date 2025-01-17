<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryCostController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

//Route::get('/', function () {
//    return Inertia::render('Welcome', [
//        'canLogin' => Route::has('login'),
//        'canRegister' => Route::has('register'),
//        'laravelVersion' => Application::VERSION,
//        'phpVersion' => PHP_VERSION,
//    ]);
//});
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

        Route::post('/store/{callId?}', [ClientController::class, 'store'])->name('clients.store');

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
});

Route::resource('leads', LeadController::class)
    ->only(['index', 'store', 'update', 'destroy', 'show'])
    ->middleware(['auth', 'verified', 'can:manage-leads']);

Route::resource('tasks', TaskController::class)
    ->only(['index', 'store', 'update', 'destroy', 'show'])
    ->middleware(['auth', 'verified', 'can:manage-tasks']);

Route::resource('calls', CallController::class)
    ->only(['index', 'store', 'update', 'destroy', 'show'])
    ->middleware(['auth', 'verified', 'can:manage-leads']);


require __DIR__ . '/auth.php';
