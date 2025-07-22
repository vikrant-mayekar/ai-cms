<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('cors')->group(function () {
    // Public routes (no authentication required)
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    // Protected routes (authentication required)
    Route::middleware('auth:api')->group(function () {
        // Auth routes
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('user-profile', [AuthController::class, 'userProfile']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // Article routes
        Route::apiResource('articles', ArticleController::class);
        Route::post('articles/{article}/publish', [ArticleController::class, 'publish']);
        Route::post('articles/{article}/archive', [ArticleController::class, 'archive']);
        Route::post('articles/{article}/regenerate-slug', [ArticleController::class, 'regenerateSlug']);
        Route::post('articles/{article}/regenerate-summary', [ArticleController::class, 'regenerateSummary']);
        Route::post('articles/regenerate-slug', [ArticleController::class, 'generateSlugFromContent']);
        Route::post('articles/regenerate-summary', [ArticleController::class, 'generateSummaryFromContent']);
        // Route::get('articles-statistics', [ArticleController::class, 'statistics']);
        Route::get('/articles-statistics', [ArticleController::class, 'statistics']);
        Route::get('/categories-statistics', [CategoryController::class, 'statistics']);

        // Category routes
        Route::apiResource('categories', CategoryController::class);
        Route::get('categories-for-select', [CategoryController::class, 'forSelect']);
        // Route::get('categories-statistics', [CategoryController::class, 'statistics']);
    });
});