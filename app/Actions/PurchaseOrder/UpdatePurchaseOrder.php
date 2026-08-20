<?php

namespace App\Actions\PurchaseOrder;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdatePurchaseOrder
{
    public function execute(PurchaseOrder $po, User $user, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data) {
            $po->update([
                'po_number' => $data['po_number'],
                'supplier_rm_id' => $data['supplier_rm_id'],
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $po->schedules()->delete();
            $po->items()->delete();

            foreach ($data['items'] as $itemData) {
                $poItem = $po->items()->create([
                    'item_id' => $itemData['item_id'],
                    'qty_ordered' => $itemData['qty_ordered'],
                ]);

                foreach ($itemData['schedules'] as $schedule) {
                    $po->schedules()->create([
                        'purchase_order_item_id' => $poItem->id,
                        'ohp_supplier_id' => $schedule['ohp_supplier_id'],
                        'scheduled_date' => $schedule['scheduled_date'],
                        'qty' => $schedule['qty'],
                        'status' => 'planned',
                    ]);
                }
            }

            return $po->fresh(['items.item', 'schedules.ohpSupplier', 'supplierRm']);
        });
    }
}
