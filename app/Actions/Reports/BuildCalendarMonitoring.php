<?php

namespace App\Actions\Reports;

use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\DeliverySchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BuildCalendarMonitoring
{
    /**
     * Kalender plan/aktual per supplier RM.
     *
     * @return array{
     *     month: string,
     *     days: int,
     *     suppliers: array<int, array{id: int, code: string, name: string}>,
     *     rows: array<int, array{supplier: array, cells: array<int, array<int, array<string, mixed>>}>
     * }
     */
    public function execute(
        User $user,
        string $month,
        ?int $supplierRmId = null,
        ?int $ohpSupplierId = null,
        bool $scopeToOhpCompany = false,
    ): array {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $days = $start->daysInMonth;

        $query = DeliverySchedule::query()
            ->with([
                'purchaseOrder.supplierRm:id,code,name',
                'purchaseOrderItem.item:id,item_number,description',
                'deliveryNote:id,delivery_schedule_id,delivery_date,qty',
                'ohpSupplier:id,code,name',
            ])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($inner) use ($start, $end) {
                        $inner->whereNotNull('ship_confirmed_at')
                            ->whereBetween('ship_confirmed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
                    })
                    ->orWhereHas(
                        'deliveryNote',
                        fn ($n) => $n->whereBetween('delivery_date', [$start->toDateString(), $end->toDateString()])
                    );
            });

        if ($user->hasRole(UserRole::SupplierRm) && $user->company_id) {
            $query->whereHas(
                'purchaseOrder',
                fn ($q) => $q->where('supplier_rm_id', $user->company_id)
            );
        } elseif ($supplierRmId) {
            $query->whereHas(
                'purchaseOrder',
                fn ($q) => $q->where('supplier_rm_id', $supplierRmId)
            );
        }

        if ($scopeToOhpCompany && $user->hasRole(UserRole::SupplierOhp) && $user->company_id) {
            $query->where('ohp_supplier_id', $user->company_id);
        } elseif ($ohpSupplierId) {
            $query->where('ohp_supplier_id', $ohpSupplierId);
        }

        $schedules = $query->get();

        $bySupplier = [];

        foreach ($schedules as $schedule) {
            $qty = (int) round((float) ($schedule->qty_confirmed ?? $schedule->qty));
            if ($qty <= 0) {
                continue;
            }

            $supplier = $schedule->purchaseOrder?->supplierRm;
            if (! $supplier) {
                continue;
            }

            $itemNumber = $schedule->purchaseOrderItem?->item?->item_number ?? '-';
            $planDate = $schedule->scheduled_date?->toDateString();
            $shipped = $schedule->status !== ScheduleStatus::Planned;
            $actualDate = $schedule->deliveryNote?->delivery_date?->toDateString()
                ?: $schedule->ship_confirmed_at?->toDateString()
                ?: $planDate;

            $cellDate = $shipped ? $actualDate : $planDate;
            if (! $cellDate) {
                continue;
            }

            $cellDay = (int) substr($cellDate, 8, 2);
            $cellMonth = substr($cellDate, 0, 7);
            if ($cellMonth !== $start->format('Y-m')) {
                continue;
            }

            $chip = [
                'item_number' => $itemNumber,
                'qty' => $qty,
                'shipped' => $shipped,
                'plan_date' => $planDate,
                'show_plan_note' => $shipped && $planDate && $planDate !== $actualDate,
            ];

            $bySupplier[$supplier->id]['supplier'] = [
                'id' => $supplier->id,
                'code' => $supplier->code,
                'name' => $supplier->name,
            ];
            $bySupplier[$supplier->id]['cells'][$cellDay][] = $chip;
        }

        $rows = collect($bySupplier)
            ->sortBy(fn ($row) => $row['supplier']['name'])
            ->values()
            ->map(function ($row) use ($days) {
                $cells = [];
                for ($day = 1; $day <= $days; $day++) {
                    $cells[$day] = $row['cells'][$day] ?? [];
                }

                return [
                    'supplier' => $row['supplier'],
                    'cells' => $cells,
                ];
            })
            ->all();

        return [
            'month' => $start->format('Y-m'),
            'days' => $days,
            'rows' => $rows,
        ];
    }

    public function supplierRmOptions(User $user): Collection
    {
        if ($user->hasRole(UserRole::SupplierRm)) {
            return Company::query()
                ->whereKey($user->company_id)
                ->get(['id', 'code', 'name']);
        }

        return Company::query()
            ->transactedAsRm()
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    public function supplierOhpOptions(User $user): Collection
    {
        if ($user->hasRole(UserRole::SupplierOhp)) {
            return Company::query()
                ->whereKey($user->company_id)
                ->get(['id', 'code', 'name']);
        }

        return Company::query()
            ->ohp()
            ->active()
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }
}
