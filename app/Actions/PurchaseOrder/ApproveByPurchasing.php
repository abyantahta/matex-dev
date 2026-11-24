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
use App\Support\QadRemarks;
use App\Support\QadSoap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApproveByPurchasing
{
    use LogsPoStatus;
    use NotifiesSupplierRm;

    public function __construct(private QadPurchaseOrderService $qad) {}

    public function execute(PurchaseOrder $po, User $user, array $data = []): PurchaseOrder
    {
        if ($po->status !== PoStatus::AwaitingPurchasingOk) {
            throw ValidationException::withMessages([
                'status' => 'PO tidak menunggu persetujuan Purchasing.',
            ]);
        }

        foreach ($data['items'] ?? [] as $row) {
            if (! isset($row['id'])) {
                continue;
            }

            $po->items()->whereKey($row['id'])->update([
                'unit_price' => $row['unit_price'] ?? null,
            ]);
        }

        $po->load(['items.item', 'supplierRm']);
        $result = $this->pushToQad($po, $data['qad_remarks'] ?? null);

        $po = DB::transaction(function () use ($po, $user, $data, $result) {
            $po->schedules()
                ->whereNull('ship_confirmed_at')
                ->whereDoesntHave('deliveryNote')
                ->where('qty_confirmed', 0)
                ->delete();

            $composedRemarks = QadRemarks::compose($po->po_number, $data['qad_remarks'] ?? null);

            $from = $po->status;
            $payload = [
                'status' => PoStatus::Confirmed,
                'purchasing_approved_at' => now(),
                'rejection_reason' => null,
                'qad_remarks' => $composedRemarks,
                'qad_status' => QadSyncStatus::Success,
                'qad_po_number' => $result['qad_po_number'],
                'qad_payload' => $result['payload'],
                'qad_response' => $result['response'],
            ];

            if ($result['qad_po_number']) {
                $payload['po_number'] = $result['qad_po_number'];
            }

            $po->update($payload);

            foreach ($result['line_numbers'] ?? [] as $poItemId => $lineNumber) {
                $po->items()->whereKey($poItemId)->update(['qad_line_number' => $lineNumber]);
            }

            $this->logStatus(
                $po,
                $from,
                PoStatus::Confirmed,
                'purchasing_approved',
                $user,
                "Purchasing mengonfirmasi OK — tersinkron ke QAD sebagai {$po->qad_po_number}."
                    ." · Remarks QAD: {$composedRemarks}",
                ['qad_status' => QadSyncStatus::Success->value, 'qad_po_number' => $po->qad_po_number],
            );

            return $po->fresh(['items.item', 'schedules.deliveryNote', 'deliveryNotes']);
        });

        $this->notifySupplierRm($po, new PurchaseOrderApprovedNotification($po));

        return $po;
    }

    /**
     * Push ke QAD dulu. Kalau QAD menolak / tidak merespons, PO tetap
     * AwaitingPurchasingOk — tidak boleh tercatat Confirm OK di Matex.
     *
     * @return array{success: bool, payload: array, response: array, qad_po_number: ?string, line_numbers: array<int, int>}
     */
    private function pushToQad(PurchaseOrder $po, ?string $remarks = null): array
    {
        try {
            $result = $this->qad->createFromMatexPo($po, $remarks);
        } catch (Throwable $e) {
            Log::error('QAD maintainPurchaseOrder failed', ['po_id' => $po->id, 'exception' => $e]);
            $this->abortBecauseQadFailed($po, [
                'payload' => [],
                'response' => ['error' => $e->getMessage()],
            ], $e->getMessage());
        }

        if (! ($result['success'] ?? false)) {
            $this->abortBecauseQadFailed($po, $result, QadSoap::userMessage($result));
        }

        return $result;
    }

    /**
     * @param  array{payload?: array, response?: array}  $result
     */
    private function abortBecauseQadFailed(PurchaseOrder $po, array $result, string $detail): never
    {
        $po->update([
            'qad_status' => QadSyncStatus::Failed,
            'qad_payload' => $result['payload'] ?? null,
            'qad_response' => $result['response'] ?? ['error' => $detail],
        ]);

        $message = 'QAD menolak pembuatan PO. Konfirmasi OK dibatalkan. '.$detail;

        session()->flash('error', $message);

        throw ValidationException::withMessages([
            'qad' => $message,
        ]);
    }
}
