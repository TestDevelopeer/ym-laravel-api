<?php

namespace App\Http\Controllers;

use App\Services\YandexMusicService;
use Exception;
use Illuminate\Http\JsonResponse;
use Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

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

        $apiResponse = $this->callYourApi();

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $apiResponse
        ]);

        return response()->json(['status' => 'success']);
    }

    protected function callYourApi()
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
            return json_encode(['track' => $track['track'], 'duration' => $track['duration_ms'], 'progress' => $track['progress_ms']], JSON_THROW_ON_ERROR);

        } catch (Exception $e) {
            Log::error('API request failed: ' . $e->getMessage());
            return 'Произошла ошибка при обращении к API. Попробуйте позже.';
        }
    }
}
