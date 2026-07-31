<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\InterventionController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SolutionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    Route::get('/solutions/mine', [SolutionController::class, 'mine']);

    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::post('/incidents', [IncidentController::class, 'store']);
    Route::get('/incidents/{incident}', [IncidentController::class, 'show']);
    Route::post('/incidents/{incident}/take-charge', [IncidentController::class, 'takeCharge']);
    Route::patch('/incidents/{incident}/status', [IncidentController::class, 'updateStatus']);
    Route::get('/incidents/{incident}/history', [IncidentController::class, 'history']);

    Route::get('/incidents/{incident}/interventions', [InterventionController::class, 'index']);
    Route::post('/incidents/{incident}/interventions', [InterventionController::class, 'store']);

    Route::get('/incidents/{incident}/messages', [MessageController::class, 'index']);
    Route::post('/incidents/{incident}/messages', [MessageController::class, 'store']);

    Route::post('/incidents/{incident}/evaluation', [EvaluationController::class, 'store']);

    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->middleware('role:admin');
    Route::get('/dashboard/technicien', [DashboardController::class, 'technicien'])->middleware('role:technicien');
    Route::get('/dashboard/entreprise', [DashboardController::class, 'entreprise'])->middleware('role:entreprise');

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);

        Route::get('/companies', [CompanyController::class, 'index']);
        Route::post('/companies', [CompanyController::class, 'store']);
        Route::get('/companies/{company}', [CompanyController::class, 'show']);
        Route::put('/companies/{company}', [CompanyController::class, 'update']);
        Route::put('/companies/{company}/solutions', [CompanyController::class, 'syncSolutions']);

        Route::post('/solutions', [SolutionController::class, 'store']);
        Route::put('/solutions/{solution}', [SolutionController::class, 'update']);
        Route::put('/solutions/{solution}/technicians', [SolutionController::class, 'syncTechnicians']);
    });

    Route::get('/solutions', [SolutionController::class, 'index']);
    Route::get('/solutions/{solution}', [SolutionController::class, 'show']);
});
