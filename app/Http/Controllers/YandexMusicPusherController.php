<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\TrackYandexMusicJob;
use App\Services\YandexMusicService;

class YandexMusicPusherController extends Controller
{
    protected $yandexMusicService;

    public function __construct(YandexMusicService $yandexMusicService)
    {
        $this->yandexMusicService = $yandexMusicService;
    }

    public function getCurrentTrack(Request $request)
    {
        $yaToken = config('app.yandex_token');
        if (!$yaToken) {
            return response()->json(['error' => 'Yandex Music Token is required'], 400);
        }
        $this->yandexMusicService->setToken($yaToken);
        $trackData = $this->yandexMusicService->getCurrentTrackBeta();

        event(new \App\Events\YandexMusicTrackUpdated($trackData));

        return response()->json($trackData);
    }

    public function startTracking(Request $request)
    {
        $yaToken = config('app.yandex_token');
        $interval = $request->input('interval', 2);

        if (!$yaToken) {
            return response()->json(['error' => 'Token is required'], 400);
        }

        // Отправляем задачу в очередь с задержкой
        TrackYandexMusicJob::dispatch($yaToken)
            ->delay(now()->addSeconds($interval));

        return response()->json([
            'status' => 'tracking started',
            'interval' => $interval
        ]);
    }
}
