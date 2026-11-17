<?php

namespace App\Support;

use App\Models\DeliveryNote;
use App\Models\PurchaseOrderItem;

class DnQtyCap
{
    /**
     * Qty PO yang mengikat surat jalan: qty yang di-ACC Purchasing
     * (lalu tembak QAD), bukan draft order awal.
     */
    public static function poQty(PurchaseOrderItem $item): int
    {
        return (int) round((float) ($item->qty_confirmed ?? $item->qty_ordered));
    }

    public static function otherNotesQty(DeliveryNote $note): int
    {
        return (int) DeliveryNote::query()
            ->where('purchase_order_item_id', $note->purchase_order_item_id)
            ->whereKeyNot($note->id)
            ->sum('qty');
    }

    public static function maxFor(DeliveryNote $note): int
    {
        $note->loadMissing('purchaseOrderItem');

        return max(0, self::poQty($note->purchaseOrderItem) - self::otherNotesQty($note));
    }
}
