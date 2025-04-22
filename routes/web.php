<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QnaController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PromptController;
use App\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to login
Route::redirect('/', '/admin/login');

// Auth routes
Route::group(['prefix' => 'admin'], function () {
    // Authentication
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Protected routes (require login)
    Route::middleware(['auth'])->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        
        // QnA Management
        Route::resource('qna', QnaController::class);
        
        // Log Management
        Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
        Route::get('/logs/{log}', [LogController::class, 'show'])->name('logs.show');
        Route::post('/logs/{log}/add-to-qna', [LogController::class, 'addToQna'])->name('logs.add-to-qna');
        Route::post('/logs/clean', [LogController::class, 'clean'])->name('logs.clean');
        Route::get('/logs/export', [LogController::class, 'export'])->name('logs.export');
        
        // Prompt Management
        Route::resource('prompts', PromptController::class);
        Route::post('/prompts/{prompt}/activate', [PromptController::class, 'activate'])->name('prompts.activate');
        
        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/generate-token', [SettingsController::class, 'generateToken'])->name('settings.generate-token');
        Route::post('/settings/test-openai', [SettingsController::class, 'testOpenAi'])->name('settings.test-openai');
    });
});