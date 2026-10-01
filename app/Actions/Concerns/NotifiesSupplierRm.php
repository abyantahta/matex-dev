<?php

namespace App\Actions\Concerns;

use App\Enums\UserRole;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Notifies every Supplier RM user on a PO's company — used at both the
 * "submit to RM" and "Purchasing approved" points in the PO lifecycle.
 * Never lets a notification failure (mail, WhatsApp, whatever channel)
 * block or roll back the action that triggered it.
 */
trait NotifiesSupplierRm
{
    private function notifySupplierRm(PurchaseOrder $po, Notification $notification): void
    {
        try {
            $po->loadMissing('supplierRm.users');

            $recipients = $po->supplierRm->users->filter(
                fn (User $recipient) => $recipient->hasRole(UserRole::SupplierRm)
            );

            NotificationFacade::send($recipients, $notification);
        } catch (Throwable $e) {
            Log::error('Failed to notify Supplier RM', [
                'po_id' => $po->id,
                'notification' => get_class($notification),
                'exception' => $e,
            ]);
        }
    }
}
