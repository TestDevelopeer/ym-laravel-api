<?php

use App\Http\Controllers\TelegramBotController;
use App\Http\Controllers\YandexMusicController;
use App\Http\Controllers\YandexMusicPusherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/get_current_track_beta', [YandexMusicController::class, 'getCurrentTrackBeta']);

Route::get('/yandex-music/current-track', [YandexMusicPusherController::class, 'getCurrentTrack']);
Route::post('/yandex-music/start-tracking', [YandexMusicPusherController::class, 'startTracking']);
Route::post('/telegram-webhook', [TelegramBotController::class, 'handleWebhook']);
