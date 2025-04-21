<?php

use App\Http\Controllers\MusicController;
use App\Http\Controllers\YandexMusicController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', [YandexMusicController::class, 'getCurrentTrack']);

Route::get('/get_current_track_beta', [YandexMusicController::class, 'getCurrentTrack'])->name('getCurrentTrackBeta');
