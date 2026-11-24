<?php

namespace App\Support;

/**
 * Parser pesan SOAP QAD — dipakai PO (maintainPurchaseOrder) dan receiving
 * (receivePurchaseOrder) supaya error QAD yang sama tampil ke user, bukan
 * dianggap sukses di Matex.
 */
class QadSoap
{
    public static function result(?string $body): ?string
    {
        if (! $body) {
            return null;
        }

        if (preg_match('/<(?:\w+:)?result>([^<]+)<\/(?:\w+:)?result>/i', $body, $m)) {
            return strtolower(trim($m[1]));
        }

        return null;
    }

    public static function isFault(?string $body): bool
    {
        return is_string($body) && stripos($body, ':Fault') !== false;
    }

    public static function faultString(?string $body): ?string
    {
        if (! $body) {
            return null;
        }

        if (preg_match('/<(?:\w+:)?faultstring>([^<]+)<\/(?:\w+:)?faultstring>/i', $body, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8'));
        }

        return null;
    }

    /** Gabungkan tt_msg_desc + tt_msg_field jadi satu string ringkas per pesan. */
    public static function warnings(?string $body): ?string
    {
        if (! $body) {
            return null;
        }

        if (! preg_match_all('/<(?:\w+:)?tt_msg_desc>([^<]*)<\/(?:\w+:)?tt_msg_desc>/i', $body, $descMatches)) {
            return null;
        }

        preg_match_all('/<(?:\w+:)?tt_msg_field>([^<]*)<\/(?:\w+:)?tt_msg_field>/i', $body, $fieldMatches);

        $messages = [];
        foreach ($descMatches[1] as $i => $desc) {
            $desc = trim($desc);
            if ($desc === '') {
                continue;
            }
            $field = trim($fieldMatches[1][$i] ?? '');
            $messages[] = $field !== '' ? "{$desc} (field: {$field})" : $desc;
        }

        return $messages ? implode('; ', array_unique($messages)) : null;
    }

    /**
     * Pesan yang aman ditampilkan ke user dari hasil service QAD
     * ({success, response: {warnings, error, raw, ...}}).
     *
     * @param  array{success?: bool, successful?: bool, status?: int, response?: array|string}  $result
     */
    public static function userMessage(array $result, string $fallback = 'QAD menolak permintaan tanpa pesan detail.'): string
    {
        $response = $result['response'] ?? [];
        if (is_string($response)) {
            $response = ['raw' => $response];
        }

        foreach (['warnings', 'error'] as $key) {
            $value = trim((string) ($response[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $raw = is_string($response['raw'] ?? null) ? $response['raw'] : '';
        $fromXml = self::warnings($raw) ?? self::faultString($raw);
        if ($fromXml) {
            return $fromXml;
        }

        $ok = $result['success'] ?? $result['successful'] ?? true;
        if (! $ok) {
            $status = $result['status'] ?? $response['status'] ?? null;

            return $status
                ? "QAD tidak merespons (HTTP {$status})."
                : 'QAD tidak merespons. Cek koneksi ke server QAD.';
        }

        return $fallback;
    }
}
