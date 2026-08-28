<?php

namespace App\Http\Controllers;

use App\Actions\PurchaseOrder\ApproveByPurchasing;
use App\Actions\PurchaseOrder\ConfirmByRm;
use App\Actions\PurchaseOrder\RejectByPurchasing;
use App\Actions\PurchaseOrder\StorePurchaseOrder;
use App\Actions\PurchaseOrder\SubmitPurchaseOrder;
use App\Actions\PurchaseOrder\UpdatePurchaseOrder;
use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use App\Http\Requests\ConfirmByRmRequest;
use App\Http\Requests\RejectPurchaseOrderRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\Company;
use App\Models\Item;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $user = $request->user();

        $orders = PurchaseOrder::query()
            ->visibleTo($user)
            ->withFulfillmentCounts()
            ->with(['supplierRm', 'creator'])
            ->withSchedulesVisibleTo($user)
            ->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PurchaseOrder $po) => tap($po)->append('is_closed'));

        return Inertia::render('Po/Index', [
            'orders' => $orders,
            'filters' => [
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PurchaseOrder::class);

        return Inertia::render('Po/Form', [
            'order' => null,
            'suppliersRm' => Company::rawMat()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'suppliersOhp' => Company::ohp()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'items' => Item::active()
                ->orderBy('item_number')
                ->get(['id', 'item_number', 'description', 'uom', 'subcont_ohp_id']),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request, StorePurchaseOrder $action): RedirectResponse
    {
        $po = $action->execute($request->user(), $request->validated());

        return redirect()
            ->route('purchase-orders.show', $po)
            ->with('success', 'Draft PO berhasil dibuat.');
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('view', $purchaseOrder);

        $user = $request->user();
        $isOhp = $user->hasRole(UserRole::SupplierOhp);

        $purchaseOrder->load([
            'supplierRm',
            'creator',
            'items.item.subcontOhp',
            'schedules' => function ($q) use ($isOhp, $user) {
                if ($isOhp && $user->company_id) {
                    $q->where('ohp_supplier_id', $user->company_id);
                }
            },
            'schedules.ohpSupplier',
            'schedules.purchaseOrderItem.item',
            'schedules.deliveryNote.ohpConfirmation',
            'schedules.deliveryNote.receiving',
            'deliveryNotes' => function ($q) use ($isOhp, $user) {
                if ($isOhp && $user->company_id) {
                    $q->whereHas(
                        'deliverySchedule',
                        fn ($sq) => $sq->where('ohp_supplier_id', $user->company_id)
                    );
                }
            },
            'deliveryNotes.purchaseOrderItem.item',
            'deliveryNotes.deliverySchedule.ohpSupplier',
            'statusLogs.user',
        ]);

        $purchaseOrder->loadCount([
            'schedules',
            'schedules as unapproved_schedules_count' => fn ($q) => $q->whereNotIn(
                'status',
                ScheduleStatus::sentAndApprovedValues()
            ),
        ]);
        $purchaseOrder->append('is_closed');
        $purchaseOrder->restrictRelationsFor($user);

        // OHP tidak melihat history internal SDI ↔ Supplier RM
        if ($isOhp) {
            $purchaseOrder->unsetRelation('statusLogs');
            $purchaseOrder->setRelation('statusLogs', collect());
        }

        return Inertia::render('Po/Show', [
            'order' => $purchaseOrder,
            'hideInternalHistory' => $isOhp,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('update', $purchaseOrder);

        $purchaseOrder->load(['items.item', 'schedules']);

        return Inertia::render('Po/Form', [
            'order' => $purchaseOrder,
            'suppliersRm' => Company::rawMat()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'suppliersOhp' => Company::ohp()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'items' => Item::active()
                ->orderBy('item_number')
                ->get(['id', 'item_number', 'description', 'uom', 'subcont_ohp_id']),
        ]);
    }

    public function update(
        UpdatePurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder,
        UpdatePurchaseOrder $action,
    ): RedirectResponse {
        $action->execute($purchaseOrder, $request->user(), $request->validated());

        return redirect()
            ->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Draft PO berhasil diperbarui.');
    }

    public function submit(PurchaseOrder $purchaseOrder, SubmitPurchaseOrder $action): RedirectResponse
    {
        $this->authorize('submit', $purchaseOrder);
        $action->execute($purchaseOrder, request()->user());

        return back()->with('success', 'PO berhasil disubmit ke Supplier RM.');
    }

    public function confirmRm(
        ConfirmByRmRequest $request,
        PurchaseOrder $purchaseOrder,
        ConfirmByRm $action,
    ): RedirectResponse {
        $action->execute($purchaseOrder, $request->user(), $request->validated());

        return back()->with('success', 'Konfirmasi RM berhasil dikirim. Menunggu OK Purchasing.');
    }

    public function approve(
        PurchaseOrder $purchaseOrder,
        ApproveByPurchasing $action,
    ): RedirectResponse {
        $this->authorize('approveAsPurchasing', $purchaseOrder);
        $action->execute($purchaseOrder, request()->user());

        return back()->with('success', 'PO dikonfirmasi OK. Delivery Notes telah digenerate.');
    }

    public function reject(
        RejectPurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder,
        RejectByPurchasing $action,
    ): RedirectResponse {
        $action->execute($purchaseOrder, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Konfirmasi ditolak. Supplier RM perlu submit ulang.');
    }
}
