import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import CalendarTable from '@/Pages/Monitoring/CalendarTable';
import { Head, router } from '@inertiajs/react';

export default function Kedatangan({
    calendar,
    filters,
    suppliersRm,
    suppliersOhp,
    showOhpFilter,
}) {
    const updateFilter = (key, value) => {
        router.get(
            route('monitoring.kedatangan'),
            { ...filters, [key]: value || undefined },
            { preserveState: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <p className="ui-eyebrow">Monitoring</p>
                    <h2 className="ui-section-title mt-1">Monitoring Kedatangan di OHP</h2>
                    <p className="mt-1 text-sm text-ink-muted">
                        Material yang datang ke OHP, dari supplier mana, item apa, dan berapa kg.
                    </p>
                </div>
            }
        >
            <Head title="Monitoring Kedatangan OHP" />

            <div className="ui-page space-y-4">
                <div className="flex flex-wrap gap-3">
                    <input
                        type="month"
                        className="rounded-xl border-line text-sm"
                        value={filters.month}
                        onChange={(e) => updateFilter('month', e.target.value)}
                    />
                    <select
                        className="rounded-xl border-line text-sm"
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
                    {showOhpFilter && (
                        <select
                            className="rounded-xl border-line text-sm"
                            value={filters.ohp_supplier_id || ''}
                            onChange={(e) => updateFilter('ohp_supplier_id', e.target.value)}
                        >
                            <option value="">Semua supplier OHP</option>
                            {suppliersOhp.map((supplier) => (
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
