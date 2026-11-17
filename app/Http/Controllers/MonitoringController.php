<?php

namespace App\Http\Controllers;

use App\Actions\Reports\BuildCalendarMonitoring;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    public function pengiriman(Request $request, BuildCalendarMonitoring $builder): Response
    {
        abort_unless(
            $request->user()?->hasRole(UserRole::Purchasing, UserRole::Admin, UserRole::SupplierRm),
            403
        );

        $user = $request->user();
        $month = $this->month($request);
        $supplierRmId = $user->hasRole(UserRole::SupplierRm)
            ? $user->company_id
            : ($request->integer('supplier_rm_id') ?: null);

        $calendar = $builder->execute($user, $month, $supplierRmId);

        return Inertia::render('Monitoring/Pengiriman', [
            'calendar' => $calendar,
            'filters' => [
                'month' => $month,
                'supplier_rm_id' => $supplierRmId,
            ],
            'suppliersRm' => $builder->supplierRmOptions($user),
            'lockSupplier' => $user->hasRole(UserRole::SupplierRm),
        ]);
    }

    public function kedatangan(Request $request, BuildCalendarMonitoring $builder): Response
    {
        abort_unless(
            $request->user()?->hasRole(UserRole::Purchasing, UserRole::Admin, UserRole::SupplierOhp),
            403
        );

        $user = $request->user();
        $month = $this->month($request);
        $supplierRmId = $request->integer('supplier_rm_id') ?: null;
        $ohpSupplierId = $user->hasRole(UserRole::SupplierOhp)
            ? $user->company_id
            : ($request->integer('ohp_supplier_id') ?: null);

        $calendar = $builder->execute(
            $user,
            $month,
            $supplierRmId,
            $ohpSupplierId,
            scopeToOhpCompany: $user->hasRole(UserRole::SupplierOhp),
        );

        return Inertia::render('Monitoring/Kedatangan', [
            'calendar' => $calendar,
            'filters' => [
                'month' => $month,
                'supplier_rm_id' => $supplierRmId,
                'ohp_supplier_id' => $ohpSupplierId,
            ],
            'suppliersRm' => $builder->supplierRmOptions($user),
            'suppliersOhp' => $builder->supplierOhpOptions($user),
            'showOhpFilter' => $user->hasRole(UserRole::Purchasing, UserRole::Admin),
        ]);
    }

    private function month(Request $request): string
    {
        $month = $request->string('month')->toString();

        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : now()->format('Y-m');
    }
}
