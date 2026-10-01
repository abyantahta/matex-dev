<?php

namespace App\Models;

use App\Enums\QadSyncStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receiving extends Model
{
    protected $fillable = [
        'delivery_note_id',
        'delivery_schedule_id',
        'received_qty',
        'received_by',
        'received_at',
        'qad_status',
        'qad_payload',
        'qad_response',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_qty' => 'integer',
            'received_at' => 'datetime',
            'qad_status' => QadSyncStatus::class,
            'qad_payload' => 'array',
            'qad_response' => 'array',
        ];
    }

    /**
     * Hanya receiving yang benar-benar terposting di QAD yang dihitung sebagai
     * "diterima". Baris gagal disimpan sebagai jejak percobaan + pesan error,
     * tapi tidak mengurangi sisa qty DN dan tidak masuk billing.
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->where('qad_status', QadSyncStatus::Success);
    }

    public function isPosted(): bool
    {
        return $this->qad_status === QadSyncStatus::Success;
    }

    /**
     * Pesan kegagalan push QAD yang bisa dibaca manusia (null jika sukses/pending).
     * Diambil dari warnings/error di qad_response, lalu diterjemahkan untuk
     * kasus yang sudah dikenal agar PPIC tahu harus berbuat apa.
     */
    protected function qadErrorMessage(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->qad_status !== QadSyncStatus::Failed) {
                return null;
            }

            $response = $this->qad_response ?? [];
            $raw = trim((string) ($response['warnings'] ?? $response['error'] ?? ''));

            if ($raw === '') {
                return 'QAD tidak memberikan detail kesalahan — cek log/koneksi QAD.';
            }

            if (preg_match('/Period has been closed for entity\s*([\w-]+)/i', $raw, $m)) {
                return "Periode akuntansi QAD untuk tanggal hari ini (entity {$m[1]}) belum dibuka atau sudah ditutup. "
                    .'Minta tim Finance/QAD membuka periode tersebut, lalu klik "Kirim ulang ke QAD".';
            }

            if (stripos($raw, 'Purchase Order closed') !== false) {
                return 'PO ini sudah berstatus closed di QAD — receiving tidak bisa diposting lagi.';
            }

            return 'QAD menolak receiving: '.$raw;
        });
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function deliverySchedule(): BelongsTo
    {
        return $this->belongsTo(DeliverySchedule::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
