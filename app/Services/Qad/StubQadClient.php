<?php

namespace App\Services\Qad;

use App\Models\Receiving;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StubQadClient implements QadClientInterface
{
    public function receive(Receiving $receiving): array
    {
        $receiving->loadMissing([
            'deliveryNote.purchaseOrder.supplierRm',
            'deliveryNote.purchaseOrderItem.item',
        ]);

        $dn = $receiving->deliveryNote;
        $po = $dn->purchaseOrder;
        $item = $dn->purchaseOrderItem->item;

        $payload = [
            'transaction_id' => (string) Str::uuid(),
            'po_number' => $po->po_number,
            'dn_number' => $dn->dn_number,
            'supplier_code' => $po->supplierRm->code,
            'item_number' => $item->item_number,
            'qty' => (float) $receiving->received_qty,
            'uom' => $item->uom,
            'received_at' => $receiving->received_at?->toIso8601String(),
            'site' => 'SDI',
        ];

        $response = [
            'status' => 'OK',
            'qad_receipt_id' => 'STUB-'.strtoupper(Str::random(8)),
            'message' => 'Receiving accepted by QAD stub',
            'timestamp' => now()->toIso8601String(),
        ];

        Log::info('QAD stub receive', [
            'payload' => $payload,
            'response' => $response,
        ]);

        return [
            'success' => true,
            'payload' => $payload,
            'response' => $response,
        ];
    }
}
