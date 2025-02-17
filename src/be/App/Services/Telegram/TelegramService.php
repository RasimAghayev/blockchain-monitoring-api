<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    protected $botToken;
    protected $chatId;

    /**
     *
     */
    public function __construct()
    {
        $this->botToken = env('TELEGRAM_BOT_TOKEN');
        $this->chatId = env('TELEGRAM_CHAT_ID');
    }

    /**
     * @param $message
     * @return void
     */
    public function sendMessage($message): void
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        Http::post($url, [
            'chat_id' => $this->chatId,
            'text' => $message,
        ]);
    }
}