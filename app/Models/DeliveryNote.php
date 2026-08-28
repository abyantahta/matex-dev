<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function receiving(): HasOne
    {
        return $this->hasOne(Receiving::class);
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
