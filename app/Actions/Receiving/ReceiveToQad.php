<?php

namespace App\Actions\Receiving;

use App\Actions\Concerns\LogsPoStatus;
use App\Enums\PoStatus;
use App\Enums\QadSyncStatus;
use App\Enums\ScheduleStatus;
use App\Models\DeliveryNote;
use App\Models\Receiving;
use App\Models\User;
use App\Services\Qad\QadClientInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiveToQad
{
    use LogsPoStatus;

    public function __construct(private QadClientInterface $qad) {}

    public function execute(DeliveryNote $dn, User $user, ?float $receivedQty = null, ?string $notes = null): Receiving
    {
        $schedule = $dn->deliverySchedule;

        if ($schedule->status !== ScheduleStatus::OhpOk) {
            throw ValidationException::withMessages([
                'status' => 'DN belum dikonfirmasi OK oleh OHP.',
            ]);
        }

        if ($dn->receiving) {
            throw ValidationException::withMessages([
                'status' => 'DN sudah di-receive.',
            ]);
        }

        return DB::transaction(function () use ($dn, $schedule, $user, $receivedQty, $notes) {
            $qty = $receivedQty ?? (float) $dn->qty;

            $receiving = Receiving::create([
                'delivery_note_id' => $dn->id,
                'delivery_schedule_id' => $schedule->id,
                'received_qty' => $qty,
                'received_by' => $user->id,
                'received_at' => now(),
                'qad_status' => QadSyncStatus::Pending,
                'notes' => $notes,
            ]);

            $result = $this->qad->receive($receiving);

            $receiving->update([
                'qad_status' => $result['success'] ? QadSyncStatus::Success : QadSyncStatus::Failed,
                'qad_payload' => $result['payload'],
                'qad_response' => $result['response'],
            ]);

            $schedule->update([
                'status' => ScheduleStatus::Received,
            ]);

            $po = $dn->purchaseOrder->fresh(['schedules']);
            $allReceived = $po->schedules->every(
                fn ($s) => $s->status === ScheduleStatus::Received
            );

            if ($allReceived) {
                $from = $po->status;
                $po->update(['status' => PoStatus::Completed]);
                $this->logStatus(
                    $po,
                    $from,
                    PoStatus::Completed,
                    'received',
                    $user,
                    "Receiving DN {$dn->dn_number} — seluruh jadwal selesai"
                );
            } else {
                $this->logStatus(
                    $po,
                    $po->status,
                    $po->status,
                    'received',
                    $user,
                    "Receiving DN {$dn->dn_number} dan push ke QAD"
                );
            }

            return $receiving->fresh(['deliveryNote', 'receiver']);
        });
    }
}
