<?php

namespace App\Http\Controllers;

use App\Services\YandexMusicService;
use Illuminate\Http\Request;

class YandexMusicController extends Controller
{
    protected YandexMusicService $yandexMusicService;
    public function __construct()
    {
        $this->yandexMusicService = new YandexMusicService();
    }
    public function getCurrentTrack(Request $request){
        $yaToken = config('app.yandex_token');
        if (!$yaToken) {
            return response()->json(['error' => 'Yandex Music Token is required'], 400);
        }

        $this->yandexMusicService->setToken($yaToken);

        $track = $this->yandexMusicService->getCurrentTrackBeta();
        if (!isset($track['track'])) {
            $track = null;
        }
        return view('welcome', ['track' => $track['track'], 'duration' => $track['duration_ms'], 'progress' => $track['progress_ms']]);
    }
}
