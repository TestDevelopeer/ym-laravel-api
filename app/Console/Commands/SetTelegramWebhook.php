<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:webhook';
    protected $description = 'Set Telegram webhook URL';

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        $telegram = new Api(config('app.telegram_token'));

        $url = url('/api/telegram-webhook', [], true);

        $response = $telegram->setWebhook(['url' => $url]);

        if ($response) {
            $this->info('Webhook установлен: ' . $url);
        } else {
            $this->error('Не удалось установить webhook');
        }
    }
}
