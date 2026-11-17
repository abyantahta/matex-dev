<?php

namespace App\Actions\DeliveryNote;

use App\Models\DeliveryNote;
use App\Models\DeliverySchedule;
use App\Models\PurchaseOrder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class GenerateDns
{
    public function execute(PurchaseOrder $po): void
    {
        $po->loadMissing(['schedules.purchaseOrderItem', 'schedules.deliveryNote']);

        foreach ($po->schedules as $index => $schedule) {
            if ($schedule->deliveryNote) {
                continue;
            }

            $qty = (int) round((float) ($schedule->qty_confirmed ?? $schedule->qty));
            if ($qty <= 0) {
                continue;
            }

            if (! filled($schedule->rm_sj_number)) {
                throw ValidationException::withMessages([
                    'rm_sj_number' => "Jadwal #{$schedule->id} belum punya nomor surat jalan internal RM.",
                ]);
            }

            $this->createForSchedule($po, $schedule, $index + 1);
        }
    }

    public function executeForSchedule(
        DeliverySchedule $schedule,
        string $rmSjNumber,
        ?string $deliveryDate = null,
    ): DeliveryNote {
        $schedule->loadMissing(['deliveryNote', 'purchaseOrder', 'purchaseOrderItem']);

        if ($schedule->deliveryNote) {
            throw ValidationException::withMessages([
                'schedule' => 'DN untuk jadwal ini sudah digenerate.',
            ]);
        }

        $rmSjNumber = trim($rmSjNumber);
        if ($rmSjNumber === '') {
            throw ValidationException::withMessages([
                'rm_sj_number' => 'Nomor surat jalan internal RM wajib diisi.',
            ]);
        }

        $schedule->update(['rm_sj_number' => $rmSjNumber]);

        $po = $schedule->purchaseOrder;
        $seq = $po->schedules()->count();
        $existingCount = $po->deliveryNotes()->count();

        return $this->createForSchedule(
            $po,
            $schedule->fresh(),
            $existingCount + 1 ?: $seq,
            $deliveryDate,
        );
    }

    private function createForSchedule(
        PurchaseOrder $po,
        DeliverySchedule $schedule,
        int $seq,
        ?string $deliveryDate = null,
    ): DeliveryNote {
        $qty = (int) round((float) ($schedule->qty_confirmed ?? $schedule->qty));
        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'qty' => 'Jadwal 0 kg tidak digenerate menjadi Delivery Note.',
            ]);
        }

        $date = $deliveryDate ?: $schedule->scheduled_date;

        return DeliveryNote::create([
            'dn_number' => $this->makeDnNumber($po, $schedule, $seq, $date),
            'purchase_order_id' => $po->id,
            'delivery_schedule_id' => $schedule->id,
            'purchase_order_item_id' => $schedule->purchase_order_item_id,
            'qty' => $qty,
            'delivery_date' => $deliveryDate ?: $schedule->scheduled_date,
            'rm_sj_number' => $schedule->rm_sj_number,
            'generated_at' => now(),
        ]);
    }

    /**
     * DN + yymmdd + 4 digit supplier + 5 digit PO + 3 digit sequence.
     * Contoh 2 Okt 2026 + L0297 + PO026866 + seq 1 → DN261002029726866001.
     */
    private function makeDnNumber(PurchaseOrder $po, DeliverySchedule $schedule, int $seq, mixed $date): string
    {
        $po->loadMissing('supplierRm');
        $yymmdd = Carbon::parse($date)->format('ymd');
        $supplier = str_pad(substr(preg_replace('/\D/', '', (string) $po->supplierRm?->code), -4), 4, '0', STR_PAD_LEFT);
        $poDigits = str_pad(substr(preg_replace('/\D/', '', (string) $po->po_number), -5), 5, '0', STR_PAD_LEFT);

        return sprintf('DN%s%s%s%03d', $yymmdd, $supplier, $poDigits, $seq);
    }
}
