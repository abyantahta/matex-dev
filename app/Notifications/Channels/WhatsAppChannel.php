<?php

namespace App\Notifications\Channels;

use App\Services\WhatsApp\FonnteClient;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Custom Laravel notification channel — a Notification opts in by
 * implementing toWhatsApp(), same pattern as toMail(). Failures here never
 * bubble up: a broken WhatsApp send should never take down the mail
 * channel or whatever action triggered the notification.
 */
class WhatsAppChannel
{
    public function __construct(private readonly FonnteClient $client) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $phone = method_exists($notifiable, 'routeNotificationForWhatsApp')
            ? $notifiable->routeNotificationForWhatsApp($notification)
            : ($notifiable->phone ?? null);

        if (blank($phone)) {
            Log::warning('Melewati notifikasi WhatsApp — user tidak punya nomor telepon.', [
                'notifiable_id' => $notifiable->id ?? null,
                'notification' => get_class($notification),
            ]);

            return;
        }

        try {
            $this->client->send($phone, $notification->toWhatsApp($notifiable));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim notifikasi WhatsApp', [
                'notifiable_id' => $notifiable->id ?? null,
                'notification' => get_class($notification),
                'exception' => $e,
            ]);
        }
    }
}
