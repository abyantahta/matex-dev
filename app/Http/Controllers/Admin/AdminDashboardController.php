<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DeliveryNote;
use App\Models\PurchaseOrder;
use App\Models\QadItem;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $isAdmin = $request->user()->hasRole('admin');

        $tables = [
            [
                'key' => 'qad-items',
                'label' => 'Item Master QAD',
                'description' => 'Master item hasil sync dari QAD — base data proses lain',
                'route' => 'admin.qad-items.index',
                'count' => QadItem::count(),
            ],
            [
                'key' => 'suppliers',
                'label' => 'Suppliers',
                'description' => 'Supplier RM, Supplier OHP, dan company lain',
                'route' => 'admin.companies.index',
                'count' => Company::count(),
            ],
            [
                'key' => 'discipline',
                'label' => 'Kedisiplinan RM',
                'description' => 'Raport keterlambatan kirim vs plan yang dikonfirmasi',
                'route' => 'admin.discipline.index',
                'count' => Company::query()->transactedAsRm()->count(),
            ],
        ];

        if ($isAdmin) {
            $tables[] = [
                'key' => 'users',
                'label' => 'Users',
                'description' => 'Akun portal & role akses',
                'route' => 'admin.users.index',
                'count' => User::count(),
            ];
        }

        $stats = [
            'items_qad' => QadItem::count(),
            'supplier_rm' => Company::query()->rawMat()->count(),
            'supplier_ohp' => Company::query()->ohp()->count(),
            'purchase_orders' => PurchaseOrder::count(),
            'delivery_notes' => DeliveryNote::count(),
        ];

        if ($isAdmin) {
            $stats['users'] = User::count();
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'tables' => $tables,
        ]);
    }
}
