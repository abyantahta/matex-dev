<?php

namespace App\Actions\PurchaseOrder;

use App\Actions\Concerns\LogsPoStatus;
use App\Enums\PoStatus;
use App\Enums\ScheduleStatus;
use App\Models\DeliverySchedule;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmByRm
{
    use LogsPoStatus;

    /**
     * $data['schedules'] = [{purchase_order_item_id, scheduled_date, qty_confirmed}].
     *
     * Jadwal Purchasing adalah usulan; RM bebas mix & match. Per item:
     * - tanggal rencana yang masih dipakai → update qty_confirmed
     * - tanggal rencana yang dikosongkan → qty_confirmed = 0 (baris disimpan
     *   agar Purchasing tetap melihat "plan X → 0" saat review; dibuang saat
     *   approve — lihat ApproveByPurchasing)
     * - tanggal baru dari RM → jadwal baru dengan plan (qty) 0
     * - jadwal tambahan RM (plan 0) yang dikosongkan lagi → dihapus
     * qty_confirmed item = total semua tanggalnya.
     */
    public function execute(PurchaseOrder $po, User $user, array $data): PurchaseOrder
    {
        if ($po->status !== PoStatus::AwaitingRmConfirm) {
            throw ValidationException::withMessages([
                'status' => 'PO tidak dalam status menunggu konfirmasi RM.',
            ]);
        }

        return DB::transaction(function () use ($po, $user, $data) {
            $submitted = collect($data['schedules'])
                ->groupBy('purchase_order_item_id')
                ->map(fn (Collection $rows) => $rows
                    // Dua cell dengan tanggal sama (seharusnya tidak terjadi) digabung.
                    ->groupBy('scheduled_date')
                    ->map(fn (Collection $same) => (int) $same->sum('qty_confirmed')));

            $po->loadMissing(['items.item', 'schedules']);

            foreach ($po->items as $poItem) {
                /** @var Collection<string,int> $cells tanggal => qty */
                $cells = $submitted->get($poItem->id, collect())->filter(fn (int $qty) => $qty > 0);

                $this->syncItemSchedules($po, $poItem, $cells);

                $poItem->update(['qty_confirmed' => $cells->sum()]);
            }

            $from = $po->status;
            $po->update([
                'status' => PoStatus::AwaitingPurchasingOk,
                'rm_confirmed_at' => now(),
            ]);

            $this->logStatus(
                $po,
                $from,
                PoStatus::AwaitingPurchasingOk,
                'rm_confirmed',
                $user,
                'Supplier RM mengonfirmasi qty dan jadwal'
            );

            return $po->fresh(['items.item', 'schedules']);
        });
    }

    /**
     * @param  Collection<string,int>  $cells  scheduled_date => qty_confirmed (>0)
     */
    private function syncItemSchedules(PurchaseOrder $po, PurchaseOrderItem $poItem, Collection $cells): void
    {
        $existing = $po->schedules
            ->where('purchase_order_item_id', $poItem->id)
            ->sortBy('id');

        $remaining = $cells->all();

        foreach ($existing as $schedule) {
            $date = $schedule->scheduled_date->toDateString();

            if (array_key_exists($date, $remaining)) {
                $schedule->update(['qty_confirmed' => $remaining[$date]]);
                unset($remaining[$date]);

                continue;
            }

            if ((float) $schedule->qty <= 0) {
                // Tambahan RM sebelumnya tanpa plan Purchasing — tidak ada
                // informasi yang perlu dipertahankan.
                $schedule->delete();

                continue;
            }

            $schedule->update(['qty_confirmed' => 0]);
        }

        if ($remaining === []) {
            return;
        }

        $ohpSupplierId = $existing->first()?->ohp_supplier_id
            ?? $poItem->item?->subcont_ohp_id;

        if (! $ohpSupplierId) {
            throw ValidationException::withMessages([
                'schedules' => "Part {$poItem->item?->item_number} belum punya tujuan OHP, tanggal baru tidak bisa ditambahkan.",
            ]);
        }

        foreach ($remaining as $date => $qty) {
            DeliverySchedule::create([
                'purchase_order_id' => $po->id,
                'purchase_order_item_id' => $poItem->id,
                'ohp_supplier_id' => $ohpSupplierId,
                'scheduled_date' => $date,
                'qty' => 0,
                'qty_confirmed' => $qty,
                'status' => ScheduleStatus::Planned,
            ]);
        }
    }
}
