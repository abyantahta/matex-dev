<?php

namespace App\Http\Controllers;

use App\Actions\Receiving\ReceiveToQad;
use App\Enums\ScheduleStatus;
use App\Http\Requests\ReceiveRequest;
use App\Models\DeliveryNote;
use App\Models\Receiving;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReceivingController extends Controller
{
    public function index(): Response
    {
        $ready = DeliveryNote::query()
            ->with([
                'purchaseOrder.supplierRm',
                'purchaseOrderItem.item',
                'deliverySchedule.ohpSupplier',
                'ohpConfirmation',
            ])
            ->whereHas('deliverySchedule', fn ($q) => $q->where('status', ScheduleStatus::OhpOk))
            ->whereDoesntHave('receiving')
            ->latest()
            ->get();

        $history = Receiving::query()
            ->with([
                'deliveryNote.purchaseOrder',
                'deliveryNote.purchaseOrderItem.item',
                'receiver',
            ])
            ->latest()
            ->paginate(15);

        return Inertia::render('Receiving/Index', [
            'ready' => $ready,
            'history' => $history,
        ]);
    }

    public function store(
        ReceiveRequest $request,
        DeliveryNote $deliveryNote,
        ReceiveToQad $action,
    ): RedirectResponse {
        $action->execute(
            $deliveryNote,
            $request->user(),
            $request->validated('received_qty'),
            $request->validated('notes')
        );

        return back()->with('success', 'Receiving berhasil dan telah di-push ke QAD (stub).');
    }
}
