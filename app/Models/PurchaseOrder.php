<?php

namespace App\Models;

use App\Enums\PoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'supplier_rm_id',
        'due_date',
        'status',
        'created_by',
        'notes',
        'rejection_reason',
        'submitted_at',
        'rm_confirmed_at',
        'purchasing_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PoStatus::class,
            'due_date' => 'date',
            'submitted_at' => 'datetime',
            'rm_confirmed_at' => 'datetime',
            'purchasing_approved_at' => 'datetime',
        ];
    }

    public function supplierRm(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_rm_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DeliverySchedule::class);
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PoStatusLog::class)->latest();
    }

    public function isEditable(): bool
    {
        return $this->status === PoStatus::Draft;
    }
}
