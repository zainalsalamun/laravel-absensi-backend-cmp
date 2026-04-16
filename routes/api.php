<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user()->load('shift');
})->middleware('auth:sanctum');

//login
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

//logout
Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');

//company
Route::get('/company', [App\Http\Controllers\Api\CompanyController::class, 'show'])->middleware('auth:sanctum');

//checkin
Route::post('/checkin', [App\Http\Controllers\Api\AttendanceController::class, 'checkin'])->middleware('auth:sanctum');

//checkout
Route::post('/checkout', [App\Http\Controllers\Api\AttendanceController::class, 'checkout'])->middleware('auth:sanctum');

//is checkin
Route::get('/is-checkin', [App\Http\Controllers\Api\AttendanceController::class, 'isCheckedin'])->middleware('auth:sanctum');

//update profile
Route::post('/update-profile', [App\Http\Controllers\Api\AuthController::class, 'updateProfile'])->middleware('auth:sanctum');

// ==========================================
// FACE RECOGNITION ENDPOINTS
// ==========================================

// Face enrollment - enroll/update face
Route::post('/face-enrollment', [App\Http\Controllers\Api\FaceEnrollmentController::class, 'enroll'])->middleware('auth:sanctum');

// Face enrollment - update (alias)
Route::put('/face-enrollment', [App\Http\Controllers\Api\FaceEnrollmentController::class, 'update'])->middleware('auth:sanctum');

// Face enrollment - remove
Route::delete('/face-enrollment', [App\Http\Controllers\Api\FaceEnrollmentController::class, 'remove'])->middleware('auth:sanctum');

// Face verification for attendance
Route::post('/face-verify', [App\Http\Controllers\Api\FaceEnrollmentController::class, 'verify'])->middleware('auth:sanctum');

// Check face enrollment status
Route::get('/face-status', [App\Http\Controllers\Api\FaceEnrollmentController::class, 'status'])->middleware('auth:sanctum');

// ==========================================

//create permission
Route::apiResource('/api-permissions', App\Http\Controllers\Api\PermissionController::class)->middleware('auth:sanctum');

//notes
Route::apiResource('/api-notes', App\Http\Controllers\Api\NoteController::class)->middleware('auth:sanctum');

//update fcm token
Route::post('/update-fcm-token', [App\Http\Controllers\Api\AuthController::class, 'updateFcmToken'])->middleware('auth:sanctum');

//get attendance
Route::get('/api-attendances', [App\Http\Controllers\Api\AttendanceController::class, 'index'])->middleware('auth:sanctum');

Route::get('/api-user/{id}', [App\Http\Controllers\Api\UserController::class, 'getUserId'])->middleware('auth:sanctum');

//update user
Route::post('/api-user/edit', [App\Http\Controllers\Api\UserController::class, 'updateProfile'])->middleware('auth:sanctum');



//shifts
Route::apiResource('/api-shifts', App\Http\Controllers\Api\ShiftController::class)->middleware('auth:sanctum');

//overtimes
Route::apiResource('/api-overtimes', App\Http\Controllers\Api\OvertimeController::class)->middleware('auth:sanctum');
