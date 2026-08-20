<?php

namespace App\Actions\PurchaseOrder;

use App\Actions\Concerns\LogsPoStatus;
use App\Enums\PoStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StorePurchaseOrder
{
    use LogsPoStatus;

    public function execute(User $user, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($user, $data) {
            $po = PurchaseOrder::create([
                'po_number' => $data['po_number'],
                'supplier_rm_id' => $data['supplier_rm_id'],
                'due_date' => $data['due_date'],
                'status' => PoStatus::Draft,
                'created_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

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

            $this->logStatus($po, null, PoStatus::Draft, 'created', $user);

            return $po->load(['items.item', 'schedules.ohpSupplier', 'supplierRm']);
        });
    }
}
