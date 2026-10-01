<?php

namespace App\Models;

use App\Enums\QadSyncStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliveryNote extends Model
{
    protected $fillable = [
        'dn_number',
        'purchase_order_id',
        'delivery_schedule_id',
        'purchase_order_item_id',
        'qty',
        'delivery_date',
        'rm_sj_number',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'delivery_date' => 'date',
            'generated_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function deliverySchedule(): BelongsTo
    {
        return $this->belongsTo(DeliverySchedule::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function ohpConfirmation(): HasOne
    {
        return $this->hasOne(OhpConfirmation::class);
    }

    /**
     * A DN can be received across several partial receipts (goods arriving
     * in installments) — this is the full history, source of truth for how
     * much has actually come in.
     */
    public function receivings(): HasMany
    {
        return $this->hasMany(Receiving::class);
    }

    /** Most recent receipt, if any — for a quick "last touched" display. */
    public function receiving(): HasOne
    {
        return $this->hasOne(Receiving::class)->latestOfMany();
    }

    /**
     * Percobaan receiving terakhir yang ditolak QAD (jika ada) — dipakai
     * untuk menampilkan alasan gagal di antrian "Siap Receive".
     */
    public function lastFailedReceiving(): HasOne
    {
        return $this->hasOne(Receiving::class)
            ->where('qad_status', QadSyncStatus::Failed)
            ->latestOfMany();
    }

    /**
     * Hanya receiving yang sukses terposting di QAD yang dihitung — kalau QAD
     * menolak, barang dianggap BELUM diterima dan DN tetap bisa di-receive.
     */
    public function getReceivedQtyAttribute(): int
    {
        return (int) $this->receivings
            ->filter(fn (Receiving $r) => $r->isPosted())
            ->sum('received_qty');
    }

    public function getQadErrorMessageAttribute(): ?string
    {
        return $this->lastFailedReceiving?->qad_error_message;
    }

    public function getRemainingQtyAttribute(): int
    {
        return max(0, (int) $this->qty - $this->received_qty);
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->remaining_qty <= 0;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query
            ->when(
                $user->hasRole(UserRole::SupplierRm),
                fn (Builder $q) => $q->whereHas(
                    'purchaseOrder',
                    fn (Builder $pq) => $pq->where('supplier_rm_id', $user->company_id)
                )
            )
            ->when(
                $user->hasRole(UserRole::SupplierOhp),
                fn (Builder $q) => $q->whereHas(
                    'deliverySchedule',
                    fn (Builder $sq) => $sq->where('ohp_supplier_id', $user->company_id)
                )
            );
    }
}
