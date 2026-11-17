import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import CalendarTable from '@/Pages/Monitoring/CalendarTable';
import { Head, router } from '@inertiajs/react';

export default function Pengiriman({ calendar, filters, suppliersRm, lockSupplier }) {
    const updateFilter = (key, value) => {
        router.get(
            route('monitoring.pengiriman'),
            { ...filters, [key]: value || undefined },
            { preserveState: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <p className="ui-eyebrow">Monitoring</p>
                    <h2 className="ui-section-title mt-1">Monitoring Pengiriman</h2>
                    <p className="mt-1 text-sm text-ink-muted">
                        Plan Purchasing per tanggal. Kuning = masih plan, hijau = sudah terkirim.
                    </p>
                </div>
            }
        >
            <Head title="Monitoring Pengiriman" />

            <div className="ui-page space-y-4">
                <div className="flex flex-wrap gap-3">
                    <input
                        type="month"
                        className="rounded-md border-line text-sm"
                        value={filters.month}
                        onChange={(e) => updateFilter('month', e.target.value)}
                    />
                    {!lockSupplier && (
                        <select
                            className="rounded-md border-line text-sm"
                            value={filters.supplier_rm_id || ''}
                            onChange={(e) => updateFilter('supplier_rm_id', e.target.value)}
                        >
                            <option value="">Semua supplier RM</option>
                            {suppliersRm.map((supplier) => (
                                <option key={supplier.id} value={supplier.id}>
                                    {supplier.code} — {supplier.name}
                                </option>
                            ))}
                        </select>
                    )}
                </div>

                <div className="ui-panel overflow-hidden p-3">
                    <CalendarTable calendar={calendar} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
