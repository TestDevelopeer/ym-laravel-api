<?php

namespace App\Http\Controllers;

use App\Services\YandexMusicService;
use Exception;
use Illuminate\Http\JsonResponse;
use JsonException;
use Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;

class TelegramBotController extends Controller
{
    protected YandexMusicService $yandexMusicService;
    protected Api $telegram;

    /**
     * @throws TelegramSDKException
     */
    public function __construct()
    {
        $this->yandexMusicService = new YandexMusicService();
        $this->telegram = new Api(config('app.telegram_token'));
    }

    /**
     * @throws TelegramSDKException
     * @throws JsonException
     */
    public function handleWebhook(): JsonResponse
    {
        $update = $this->telegram->getWebhookUpdate();

        $chatId = $update->getChat()->getId();
        $message = $update->getMessage()->getText();

        if ($message === '/start') {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Привет! Я бот для работы с API. Отправь мне запрос, и я обращусь к API.'
            ]);
            return response()->json(['status' => 'success']);
        }

        $trackData = $this->getCurrentTrackData();
        $this->sendTrackInfo($this->telegram, $chatId, $trackData);
        return response()->json(['status' => 'success']);
    }

    protected function getCurrentTrackData()
    {
        try {
            $yaToken = config('app.yandex_token');
            if (!$yaToken) {
                return response()->json(['error' => 'Yandex Music Token is required'], 400);
            }

            $this->yandexMusicService->setToken($yaToken);

            $track = $this->yandexMusicService->getCurrentTrackBeta();
            if (!isset($track['track'])) {
                $track = null;
            }
            return $track;

        } catch (Exception $e) {
            Log::error('API request failed: ' . $e->getMessage());
            return 'Произошла ошибка при обращении к API. Попробуйте позже.';
        }
    }

    /**
     * @throws JsonException
     */
    protected function sendTrackInfo($telegram, $chatId, $trackData): void
    {
        // Форматируем данные
        $track = $trackData['track'];
        $duration = $this->formatMilliseconds($trackData['duration_ms']);
        $progress = $this->formatMilliseconds($trackData['progress_ms']);
        $progressPercent = round($trackData['progress_ms'] / $trackData['duration_ms'] * 100);
        $progressBar = $this->generateProgressBar($progressPercent);

        $imageUrl = $track['image_url'];
        $image = InputFile::create($imageUrl, 'track_cover.jpg');

        // Основная информация о треке
        $caption = sprintf(
            "🎵 *%s* - %s\n".
            "💿 Альбом: %s\n".
            "⏱ Продолжительность: %s\n".
            "▶️ Прогресс: %s / %s\n%s\n".
            "🔗 [Слушать на Яндекс.Музыке](https://music.yandex.ru/track/%s)",
            $track['title'],
            implode(', ', array_column($track['artists'], 'name')),
            $track['albums'][0]['title'],
            $duration,
            $progress,
            $duration,
            $progressBar,
            $track['id']
        );

        if ($trackData['paused']) {
            $caption .= "\n⏸ Сейчас на паузе";
        } else {
            $caption .= "\n▶️ Сейчас играет";
        }

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '▶️ Слушать',
                        'url' => 'https://music.yandex.ru/track/'.$track['id']
                    ],
                    [
                        'text' => '💿 Альбом',
                        'url' => 'https://music.yandex.ru/album/'.$track['albums'][0]['id']
                    ]
                ]
            ]
        ];

        // Отправляем изображение с подписью
        try {
            $telegram->sendPhoto([
                'chat_id' => $chatId,
                'photo' => $image,
                'caption' => $caption,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true,
                'reply_markup' => json_encode($replyMarkup, JSON_THROW_ON_ERROR)
            ]);
        } catch (Exception) {
            // Если не удалось отправить фото, отправляем текстовое сообщение
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $caption,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true,
                'reply_markup' => json_encode($replyMarkup, JSON_THROW_ON_ERROR)
            ]);
        }
    }

    protected function formatMilliseconds($ms): string
    {
        $seconds = floor($ms / 1000);
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds %= 60;

        if ($hours > 0) {
            return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
        }

        return sprintf("%d:%02d", $minutes, $seconds);
    }

    protected function generateProgressBar($percent, $length = 20): string
    {
        $filled = round($percent / 100 * $length);
        $empty = $length - $filled;

        return '[' . str_repeat('█', $filled) . str_repeat('░', $empty) . '] ' . $percent . '%';
    }
}
