<?php

namespace App\Models;

use App\Enums\CompanyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => CompanyType::class,
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function defaultItems(): HasMany
    {
        return $this->hasMany(Item::class, 'subcont_ohp_id');
    }

    public function forecasts(): HasMany
    {
        return $this->hasMany(Forecast::class, 'supplier_rm_id');
    }

    public function purchaseOrdersAsRm(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_rm_id');
    }

    public function scopeRawMat($query)
    {
        return $query->where('type', CompanyType::RawMat);
    }

    /**
     * Supplier RM yang sudah bertransaksi: punya minimal satu PO yang sudah
     * dikonfirmasi RM (baru ada plan tanggal kirim untuk dinilai).
     * Companies di-provision dari QAD saat pertama dipakai di PO, jadi
     * scope ini sekaligus menyaring company manual/dummy tanpa transaksi.
     */
    public function scopeTransactedAsRm($query)
    {
        return $query
            ->rawMat()
            ->whereHas('purchaseOrdersAsRm', fn ($q) => $q->whereNotNull('rm_confirmed_at'));
    }

    public function scopeOhp($query)
    {
        return $query->where('type', CompanyType::Ohp);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
