<?php

namespace App\Actions\PurchaseOrder;

use App\Actions\Concerns\LogsPoStatus;
use App\Enums\PoStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveByPurchasing
{
    use LogsPoStatus;

    public function execute(PurchaseOrder $po, User $user): PurchaseOrder
    {
        if ($po->status !== PoStatus::AwaitingPurchasingOk) {
            throw ValidationException::withMessages([
                'status' => 'PO tidak menunggu persetujuan Purchasing.',
            ]);
        }

        return DB::transaction(function () use ($po, $user) {
            $from = $po->status;
            $po->update([
                'status' => PoStatus::Confirmed,
                'purchasing_approved_at' => now(),
                'rejection_reason' => null,
            ]);

            $this->logStatus(
                $po,
                $from,
                PoStatus::Confirmed,
                'purchasing_approved',
                $user,
                'Purchasing mengonfirmasi OK — menunggu Supplier RM isi no. SJ internal & generate DN'
            );

            return $po->fresh(['items.item', 'schedules.deliveryNote', 'deliveryNotes']);
        });
    }
}
