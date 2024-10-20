<?php

use App\Http\Controllers\AttemptController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SharedQuizController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/check-user', [AuthController::class, 'checkUser']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/quizzes', [QuizController::class, 'store']);
    Route::get('/quizzes', [QuizController::class, 'index']);
    Route::get('/quiz/{quiz}', [QuizController::class, 'show']);
    Route::delete('/quiz/{quiz}', [QuizController::class, 'destroy']);

    Route::post('/quiz/attempt', [AttemptController::class, 'store']);
    Route::get('/quiz/attempt/check/{quiz_id}', [AttemptController::class, 'checkAttempt']);
    Route::get('/quiz/attempt/{attemptId}', [AttemptController::class, 'show']);
    Route::post('view-all-attempts', [AttemptController::class, 'getAllAttempts']);
    Route::delete('/view-all-attempts/{attemptId}', [AttemptController::class, 'destroy']);

    Route::post('/share-quiz', [SharedQuizController::class, 'store']);
    Route::get('/quiz/{quiz_id}/shares', [SharedQuizController::class, 'getAlreadyShared']);
    Route::delete('/quiz/{shared_id}/unshare', [SharedQuizController::class, 'unshare']);

    Route::post('/search-user', [UserController::class, 'searchUser']);
    Route::get('/get-shared-quizzes', [SharedQuizController::class, 'getSharedQuizzes']);
    Route::get('/get-my-attempts', [AttemptController::class, 'getMyAttempts']);
});
