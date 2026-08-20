<?php

namespace App\Http\Controllers;

use App\Enums\PoStatus;
use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use App\Models\DeliveryNote;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $mode = $request->string('mode')->toString() === 'item' ? 'item' : 'po';
        $search = $request->string('search')->toString();
        $runningStatuses = [
            PoStatus::AwaitingRmConfirm,
            PoStatus::AwaitingPurchasingOk,
            PoStatus::Confirmed,
            PoStatus::InProgress,
        ];

        $stats = match ($user->role) {
            UserRole::Purchasing, UserRole::Admin => [
                'draft' => PurchaseOrder::where('status', PoStatus::Draft)->count(),
                'awaiting_rm' => PurchaseOrder::where('status', PoStatus::AwaitingRmConfirm)->count(),
                'awaiting_ok' => PurchaseOrder::where('status', PoStatus::AwaitingPurchasingOk)->count(),
                'confirmed' => PurchaseOrder::whereIn('status', [PoStatus::Confirmed, PoStatus::InProgress])->count(),
            ],
            UserRole::SupplierRm => [
                'to_confirm' => PurchaseOrder::where('supplier_rm_id', $user->company_id)
                    ->where('status', PoStatus::AwaitingRmConfirm)->count(),
                'to_ship' => DeliveryNote::whereHas('purchaseOrder', fn ($q) => $q->where('supplier_rm_id', $user->company_id))
                    ->whereHas('deliverySchedule', fn ($q) => $q->where('status', ScheduleStatus::Planned))->count(),
                'received' => Receiving::whereHas('deliveryNote.purchaseOrder', fn ($q) => $q->where('supplier_rm_id', $user->company_id))->count(),
            ],
            UserRole::SupplierOhp => [
                'incoming' => DeliveryNote::whereHas('deliverySchedule', fn ($q) => $q
                    ->where('ohp_supplier_id', $user->company_id)
                    ->where('status', ScheduleStatus::ShipConfirmed))->count(),
                'confirmed' => DeliveryNote::whereHas('deliverySchedule', fn ($q) => $q->where('ohp_supplier_id', $user->company_id))
                    ->whereHas('ohpConfirmation')->count(),
            ],
            UserRole::Ppic => [
                'ready_receive' => DeliveryNote::whereHas('deliverySchedule', fn ($q) => $q->where('status', ScheduleStatus::OhpOk))
                    ->whereDoesntHave('receiving')->count(),
                'received' => Receiving::count(),
            ],
            default => [],
        };

        $recentPos = PurchaseOrder::query()
            ->with(['supplierRm', 'schedules.ohpSupplier'])
            ->when($user->hasRole(UserRole::SupplierRm), fn ($q) => $q->where('supplier_rm_id', $user->company_id))
            ->when(
                $user->hasRole(UserRole::SupplierOhp),
                fn ($q) => $q->whereHas(
                    'schedules',
                    fn ($sq) => $sq->where('ohp_supplier_id', $user->company_id)
                )
            )
            ->latest()
            ->limit(8)
            ->get();

        $scopeRunningPo = function ($q) use ($user, $runningStatuses) {
            $q->whereIn('status', $runningStatuses)
                ->when($user->hasRole(UserRole::SupplierRm), fn ($inner) => $inner->where('supplier_rm_id', $user->company_id))
                ->when(
                    $user->hasRole(UserRole::SupplierOhp),
                    fn ($inner) => $inner->whereHas(
                        'schedules',
                        fn ($sq) => $sq->where('ohp_supplier_id', $user->company_id)
                    )
                );
        };

        $itemRows = Item::query()
            ->whereHas('purchaseOrderItems.purchaseOrder', $scopeRunningPo)
            ->when($search !== '', function ($q) use ($search) {
                $term = '%'.$search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('item_number', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->with([
                'purchaseOrderItems' => function ($q) use ($scopeRunningPo) {
                    $q->whereHas('purchaseOrder', $scopeRunningPo)
                        ->with(['purchaseOrder.supplierRm', 'purchaseOrder.schedules.ohpSupplier']);
                },
            ])
            ->orderBy('item_number')
            ->paginate(12)
            ->withQueryString()
            ->through(function (Item $item) {
                $pos = $item->purchaseOrderItems
                    ->groupBy('purchase_order_id')
                    ->map(function ($lines) {
                        $po = $lines->first()->purchaseOrder;
                        $qty = (int) round($lines->sum(fn ($line) => (float) ($line->qty_confirmed ?? $line->qty_ordered)));

                        return [
                            'id' => $po->id,
                            'po_number' => $po->po_number,
                            'status' => $po->status instanceof PoStatus ? $po->status->value : $po->status,
                            'supplier_rm' => $po->supplierRm,
                            'due_date' => $po->due_date,
                            'qty' => $qty,
                        ];
                    })
                    ->values();

                return [
                    'id' => $item->id,
                    'item_number' => $item->item_number,
                    'description' => $item->description,
                    'uom' => $item->uom,
                    'running_po_count' => $pos->count(),
                    'total_qty' => (int) $pos->sum('qty'),
                    'purchase_orders' => $pos,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentPos' => $recentPos,
            'itemRows' => $itemRows,
            'mode' => $mode,
            'filters' => [
                'search' => $search,
            ],
            'role' => $user->role->value,
            'roleLabel' => $user->role->label(),
        ]);
    }
}
