<?php

namespace App\Http\Controllers;

use App\Actions\Receiving\ReceiveToQad;
use App\Enums\QadSyncStatus;
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
        // status stays OhpOk until a DN's cumulative received qty reaches
        // its full qty (see ReceiveToQad), so this alone already excludes
        // fully-received DNs while still surfacing partially-received ones
        // for their remainder.
        $ready = DeliveryNote::query()
            ->with([
                'purchaseOrder.supplierRm',
                'purchaseOrderItem.item',
                'deliverySchedule.ohpSupplier',
                'ohpConfirmation',
                'receivings',
                'lastFailedReceiving',
            ])
            ->whereHas('deliverySchedule', fn ($q) => $q->where('status', ScheduleStatus::OhpOk))
            ->latest()
            ->get()
            ->each->append(['received_qty', 'remaining_qty', 'is_fully_received', 'qad_error_message']);

        $history = Receiving::query()
            ->with([
                'deliveryNote.purchaseOrder',
                'deliveryNote.purchaseOrderItem.item',
                'receiver',
            ])
            ->latest()
            ->paginate(15)
            ->through(fn (Receiving $r) => tap($r)->append('qad_error_message'));

        return Inertia::render('Receiving/Index', [
            'ready' => $ready,
            'history' => $history,
            'receivingEnabled' => (bool) config('qad.receiving_enabled'),
        ]);
    }

    public function store(
        ReceiveRequest $request,
        DeliveryNote $deliveryNote,
        ReceiveToQad $action,
    ): RedirectResponse {
        $receiving = $action->execute(
            $deliveryNote,
            $request->user(),
            $request->validated('received_qty'),
            $request->validated('notes')
        );

        return $this->respond($receiving, $deliveryNote);
    }

    public function retry(Receiving $receiving, ReceiveToQad $action): RedirectResponse
    {
        $receiving = $action->retry($receiving, request()->user());

        return $this->respond($receiving, $receiving->deliveryNote);
    }

    private function respond(Receiving $receiving, DeliveryNote $deliveryNote): RedirectResponse
    {
        if ($receiving->qad_status === QadSyncStatus::Failed) {
            return back()->with(
                'error',
                "Receiving DN {$deliveryNote->dn_number} ({$receiving->received_qty} kg) DITOLAK QAD — barang belum tercatat diterima, silakan coba lagi nanti. "
                    .$receiving->qad_error_message
            );
        }

        $message = $deliveryNote->fresh()->is_fully_received
            ? "Receiving DN {$deliveryNote->dn_number} selesai (lunas) dan terposting di QAD."
            : "Receiving parsial DN {$deliveryNote->dn_number} ({$receiving->received_qty} kg) terposting di QAD.";

        return back()->with('success', $message);
    }
}
