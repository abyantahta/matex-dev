<?php

namespace App\Actions\PurchaseOrder;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadSignedPo
{
    /**
     * Stores the signed PO PDF, replacing any previous upload — Purchasing
     * can re-upload at any time (e.g. wrong file, needs a rescan) with no
     * extra confirmation step, same as Forecast's file replace behavior.
     */
    public function execute(PurchaseOrder $po, User $user, UploadedFile $file): PurchaseOrder
    {
        if ($po->signed_po_path) {
            Storage::disk('public')->delete($po->signed_po_path);
        }

        $path = $file->store('signed-pos', 'public');

        $po->update([
            'signed_po_path' => $path,
            'signed_po_original_filename' => $file->getClientOriginalName(),
            'signed_po_uploaded_at' => now(),
            'signed_po_uploaded_by' => $user->id,
        ]);

        return $po->fresh();
    }
}
