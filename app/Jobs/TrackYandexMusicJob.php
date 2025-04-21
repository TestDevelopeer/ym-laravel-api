<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\YandexMusicService;
use Pusher\Pusher;

class TrackYandexMusicJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $yaToken;
    protected $interval;

    public function __construct($yaToken, $interval = 2)
    {
        $this->yaToken = $yaToken;
        $this->interval = $interval;
    }

    public function handle(YandexMusicService $yandexMusicService): void
    {
        try {
            $yandexMusicService->setToken($this->yaToken);
            // Получаем данные о треке
            $trackData = $yandexMusicService->getCurrentTrackBeta();

            // Отправляем через Pusher
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                [
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'useTLS' => true
                ]
            );

            $pusher->trigger(
                'yandex-music',
                'track-updated',
                $trackData
            );

            // Планируем следующее выполнение
            self::dispatch($this->yaToken, $this->interval)
                ->delay(now()->addSeconds($this->interval));

        } catch (\Exception $e) {
            \Log::error("Track update error: " . $e->getMessage());
        }
    }
}
