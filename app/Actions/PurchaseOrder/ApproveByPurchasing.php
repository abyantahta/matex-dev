<?php

namespace App\Actions\PurchaseOrder;

use App\Actions\Concerns\LogsPoStatus;
use App\Actions\Concerns\NotifiesSupplierRm;
use App\Enums\PoStatus;
use App\Enums\QadSyncStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Notifications\PurchaseOrderApprovedNotification;
use App\Services\Qad\QadPurchaseOrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApproveByPurchasing
{
    use LogsPoStatus;
    use NotifiesSupplierRm;

    public function __construct(private QadPurchaseOrderService $qad) {}

    public function execute(PurchaseOrder $po, User $user): PurchaseOrder
    {
        if ($po->status !== PoStatus::AwaitingPurchasingOk) {
            throw ValidationException::withMessages([
                'status' => 'PO tidak menunggu persetujuan Purchasing.',
            ]);
        }

        $po = DB::transaction(function () use ($po, $user) {
            // Tanggal rencana yang dikosongkan RM (qty_confirmed 0) hanya
            // dipertahankan untuk review Purchasing. Setelah OK, buang agar
            // tidak muncul sebagai jadwal/DN yang harus dikirim.
            $po->schedules()
                ->whereNull('ship_confirmed_at')
                ->whereDoesntHave('deliveryNote')
                ->where('qty_confirmed', 0)
                ->delete();

            $from = $po->status;
            $po->update([
                'status' => PoStatus::Confirmed,
                'purchasing_approved_at' => now(),
                'rejection_reason' => null,
            ]);

            $this->pushToQad($po);

            $notes = 'Purchasing mengonfirmasi OK — menunggu Supplier RM isi no. SJ internal & generate DN';
            $notes .= match ($po->qad_status) {
                QadSyncStatus::Success => " · Tersinkron ke QAD sebagai {$po->qad_po_number}.",
                QadSyncStatus::Failed => ' · Sync ke QAD gagal.',
                default => '',
            };

            $this->logStatus(
                $po,
                $from,
                PoStatus::Confirmed,
                'purchasing_approved',
                $user,
                $notes,
                $po->qad_status ? ['qad_status' => $po->qad_status->value, 'qad_po_number' => $po->qad_po_number] : null,
            );

            return $po->fresh(['items.item', 'schedules.deliveryNote', 'deliveryNotes']);
        });

        // Outside the transaction — an external mail send has no business
        // being inside a DB transaction, and this shouldn't roll back the
        // approval or block on SMTP either way.
        $this->notifySupplierRm($po, new PurchaseOrderApprovedNotification($po));

        return $po;
    }

    /**
     * Push the PO to QAD (maintainPurchaseOrder) once Purchasing has given
     * final OK — this, not RM's confirmation, is the fix point the business
     * treats as final, so this is the only place this fires. A QAD failure
     * doesn't block the Purchasing approval itself, same as the Receiving →
     * QAD stub flow.
     */
    private function pushToQad(PurchaseOrder $po): void
    {
        try {
            $result = $this->qad->createFromMatexPo($po);
        } catch (Throwable $e) {
            Log::error('QAD maintainPurchaseOrder failed', ['po_id' => $po->id, 'exception' => $e]);

            $po->update(['qad_status' => QadSyncStatus::Failed]);

            return;
        }

        $payload = [
            'qad_status' => $result['success'] ? QadSyncStatus::Success : QadSyncStatus::Failed,
            'qad_po_number' => $result['qad_po_number'],
            'qad_payload' => $result['payload'],
            'qad_response' => $result['response'],
        ];

        // Replace the DR- draft placeholder with the real QAD PO number now
        // that it's fixed — po_number is what shows everywhere in matex
        // (DN numbers, search, etc), so this is what makes "still draft"
        // vs "has a real PO number" visible at a glance.
        if ($result['success'] && $result['qad_po_number']) {
            $payload['po_number'] = $result['qad_po_number'];
        }

        $po->update($payload);

        // Persist the QAD line number per item — Receiving needs it later
        // to tell QAD which PO line a receipt applies to.
        if ($result['success']) {
            foreach ($result['line_numbers'] ?? [] as $poItemId => $lineNumber) {
                $po->items()->whereKey($poItemId)->update(['qad_line_number' => $lineNumber]);
            }
        }
    }
}
