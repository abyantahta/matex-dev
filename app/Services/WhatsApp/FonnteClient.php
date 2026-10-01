<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for Fonnte's WhatsApp gateway (https://fonnte.com) — a
 * device-token-based API, not the official Meta Business API. Token comes
 * from a connected device in the Fonnte dashboard (config('services.fonnte.token')).
 */
class FonnteClient
{
    public function send(string $phone, string $message): bool
    {
        $token = config('services.fonnte.token');

        if (blank($token)) {
            Log::warning('Fonnte token belum dikonfigurasi (FONNTE_TOKEN) — pesan WhatsApp tidak dikirim.', [
                'phone' => $phone,
            ]);

            return false;
        }

        $response = Http::withHeaders(['Authorization' => $token])
            ->asForm()
            ->timeout(15)
            ->post(config('services.fonnte.url'), [
                'target' => $this->normalizePhone($phone),
                'message' => $message,
            ]);

        if (! $response->successful() || $response->json('status') === false) {
            Log::error('Gagal mengirim WhatsApp via Fonnte', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Fonnte expects digits only with country code (62...), no leading
     * '+' or '0' — normalizes common Indonesian local formats (0812...).
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits;
    }
}
