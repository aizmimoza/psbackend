<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SurveyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
*/

/* auth - login & register */
    Route::post( '/register', [AuthController::class, 'signup'] );
    Route::post( '/login', [AuthController::class, 'signin'] );

/* survey answer view */
    Route::get( '/survey-by-slug/{survey:slug}', [SurveyController::class, 'publicSurveyView'] );
    Route::post( '/survey/{survey}/answer', [SurveyController::class, 'answerToSurveyQuestion'] );


/* protected routes */
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        
        // home dashboard
        Route::get( '/home', [HomeController::class, 'index'] );

        // crud route on surveys
        Route::resource('/survey', SurveyController::class);
        
        /* */
        Route::post( '/logout', [AuthController::class, 'signout'] );
    });


