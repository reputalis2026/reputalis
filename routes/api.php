<?php

use App\Http\Controllers\Api\SurveyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rate limit: 5 encuestas por IP por minuto (middleware throttle:surveys).
|
*/

Route::post('/surveys/create', [SurveyController::class, 'store'])
    ->middleware('throttle:surveys')
    ->name('api.surveys.create');
