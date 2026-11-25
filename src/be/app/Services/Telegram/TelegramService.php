<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    protected $botToken;

    protected $chatId;

    public function __construct()
    {
        $this->botToken = env('TELEGRAM_BOT_TOKEN');
        $this->chatId = env('TELEGRAM_CHAT_ID');
    }

    /**
     * Whether a bot token and a chat are configured.
     */
    public function isConfigured(): bool
    {
        return filled($this->botToken) && filled($this->chatId);
    }

    public function sendMessage($message): void
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        Http::post($url, [
            'chat_id' => $this->chatId,
            'text' => $message,
            // The message body uses Markdown syntax (*bold*, `code`, links) —
            // without parse_mode, Telegram renders it as literal characters
            // instead of formatting it (docs/REPOSITORY_AUDIT.md P2-6).
            'parse_mode' => 'Markdown',
        ]);
    }
}
