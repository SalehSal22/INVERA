<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\KbsAssessmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/register/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('throttle:20,1');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:30,1');
    Route::post('/update-password', [AuthController::class, 'updatePassword'])->middleware('auth:api', 'throttle:10,1');
    Route::post('/saveFcmToken', [AuthController::class, 'saveFcmToken'])->middleware('auth:api');
});

Route::middleware('auth:api')->group(function () {
    Route::post('/upload-video', [\App\Http\Controllers\UploadVideoController::class, 'upload']);
    Route::get('/videos', [\App\Http\Controllers\UploadVideoController::class, 'getVideos']);
    Route::post('/kbs/start', [KbsAssessmentController::class, 'start']);
    Route::post('/kbs/next', [KbsAssessmentController::class, 'nextQ']);
    Route::get('/kbs/video/{video}/reports', [KbsAssessmentController::class, 'getReports']);
    Route::get('home', [\App\Http\Controllers\HomeConrtoller::class, 'index']);
    Route::get('user-info', [\App\Http\Controllers\HomeConrtoller::class, 'getUserInfo']);
    Route::post('upload-photo', [\App\Http\Controllers\HomeConrtoller::class, 'uploadPhoto']);
});
Route::post('/video/{video}/status', [\App\Http\Controllers\UploadVideoController::class, 'updateStatus']);

Route::prefix('admin/auth')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->middleware('auth:admin');
    Route::get('/me', [AdminAuthController::class, 'me'])->middleware('auth:admin');
    Route::post('/change-name', [AdminAuthController::class, 'changeName'])->middleware('auth:admin');
    Route::post('/change-email', [AdminAuthController::class, 'changeEmail'])->middleware('auth:admin');
    // Route::post('/change-email/verify-otp', [AdminAuthController::class, 'verifyChangeEmailOtp'])->middleware('auth:admin');
    Route::post('/change-password', [AdminAuthController::class, 'changePassword'])->middleware('auth:admin');

    
});
Route::prefix('admin/dashboard')->middleware('auth:admin')->group(function () {
    Route::get('/report', [\App\Http\Controllers\dashboardController::class, 'getPlatformAnalytics']);

});