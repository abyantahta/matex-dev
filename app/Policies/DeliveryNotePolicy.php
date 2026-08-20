<?php

namespace App\Policies;

use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use App\Models\DeliveryNote;
use App\Models\User;

class DeliveryNotePolicy
{
    public function view(User $user, DeliveryNote $deliveryNote): bool
    {
        $po = $deliveryNote->purchaseOrder;

        if ($user->isSdiStaff() || $user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::SupplierRm)) {
            return $po->supplier_rm_id === $user->company_id;
        }

        if ($user->hasRole(UserRole::SupplierOhp)) {
            return $deliveryNote->deliverySchedule?->ohp_supplier_id === $user->company_id;
        }

        return false;
    }

    public function print(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasRole(UserRole::SupplierRm, UserRole::Purchasing, UserRole::Admin, UserRole::Ppic)
            && $this->view($user, $deliveryNote);
    }

    public function updateDeliveryDate(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasRole(UserRole::SupplierRm)
            && $deliveryNote->purchaseOrder->supplier_rm_id === $user->company_id
            && $deliveryNote->deliverySchedule?->status === ScheduleStatus::Planned;
    }

    public function confirmShipment(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasRole(UserRole::SupplierRm)
            && $deliveryNote->purchaseOrder->supplier_rm_id === $user->company_id
            && $deliveryNote->deliverySchedule->status === ScheduleStatus::Planned
            && (filled($deliveryNote->rm_sj_number)
                || filled($deliveryNote->deliverySchedule?->rm_sj_number));
    }

    public function confirmAsOhp(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasRole(UserRole::SupplierOhp)
            && $deliveryNote->deliverySchedule?->ohp_supplier_id === $user->company_id
            && $deliveryNote->deliverySchedule->status === ScheduleStatus::ShipConfirmed
            && $deliveryNote->ohpConfirmation === null;
    }

    public function receive(User $user, DeliveryNote $deliveryNote): bool
    {
        return $user->hasRole(UserRole::Ppic, UserRole::Admin)
            && $deliveryNote->deliverySchedule->status === ScheduleStatus::OhpOk
            && $deliveryNote->receiving === null;
    }
}
