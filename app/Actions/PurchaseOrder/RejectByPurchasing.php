<?php

namespace App\Actions\PurchaseOrder;

use App\Actions\Concerns\LogsPoStatus;
use App\Enums\PoStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RejectByPurchasing
{
    use LogsPoStatus;

    public function execute(PurchaseOrder $po, User $user, string $reason): PurchaseOrder
    {
        if ($po->status !== PoStatus::AwaitingPurchasingOk) {
            throw ValidationException::withMessages([
                'status' => 'PO tidak menunggu persetujuan Purchasing.',
            ]);
        }

        $from = $po->status;
        $po->update([
            'status' => PoStatus::AwaitingRmConfirm,
            'rejection_reason' => $reason,
            'rm_confirmed_at' => null,
        ]);

        // Reset konfirmasi agar Supplier RM bisa revisi dari qty rencana, bukan angka yang ditolak.
        $po->items()->update(['qty_confirmed' => null]);
        $po->schedules()->update(['qty_confirmed' => null]);

        $this->logStatus(
            $po,
            $from,
            PoStatus::AwaitingRmConfirm,
            'purchasing_rejected',
            $user,
            $reason
        );

        return $po->fresh();
    }
}
