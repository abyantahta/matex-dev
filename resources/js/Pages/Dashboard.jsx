import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatQty } from '@/utils/qty';
import { Head, Link, router } from '@inertiajs/react';
import { Fragment, useState } from 'react';

function ohpLabels(po) {
    return [
        ...new Set(
            (po.schedules || [])
                .map((s) => s.ohp_supplier?.name)
                .filter(Boolean),
        ),
    ].join(', ');
}

const STAT_TONES = [
    { card: 'bg-slate-50', chip: 'bg-white text-slate-500', text: 'text-slate-500' },
    { card: 'bg-amber-50', chip: 'bg-white text-amber-600', text: 'text-amber-700' },
    { card: 'bg-rose-50', chip: 'bg-white text-rose-500', text: 'text-rose-600' },
    { card: 'bg-sky-50', chip: 'bg-white text-sky-600', text: 'text-sky-700' },
    { card: 'bg-emerald-50', chip: 'bg-white text-emerald-600', text: 'text-emerald-700' },
    { card: 'bg-violet-50', chip: 'bg-white text-violet-600', text: 'text-violet-700' },
]

function StatCard({ label, value, index = 0 }) {
    const tone = STAT_TONES[index % STAT_TONES.length];

    return (
        <div
            className={`ui-panel ui-panel-interactive relative animate-fade-up overflow-hidden !border-white p-5 ${tone.card}`}
            style={{ animationDelay: `${index * 60}ms` }}
        >
                        <div className="relative flex items-start justify-between gap-3">
                <p className={`text-[0.6875rem] font-bold uppercase tracking-[0.12em] ${tone.text}`}>{label}</p>
                <span className={`h-9 w-9 shrink-0 rounded-xl ${tone.chip}`}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="m-2 h-5 w-5" aria-hidden="true">
                        <path d="M4 19V9m6 10V5m6 14v-6m4 6H2" />
                    </svg>
                </span>
            </div>
            <p className="relative mt-4 font-display text-4xl font-extrabold tabular-nums text-ink">
                {value ?? 0}
            </p>
        </div>
    );
}

function ModeToggle({ mode, onChange }) {
    const isItem = mode === 'item';

    return (
        <div className="flex flex-wrap items-center gap-3">
            <span
                className={`text-sm ${!isItem ? 'font-semibold text-ink' : 'text-ink-muted'}`}
            >
                PO
            </span>
            <button
                type="button"
                role="switch"
                aria-checked={isItem}
                onClick={() => onChange(isItem ? 'po' : 'item')}
                className={`relative inline-flex h-7 w-12 shrink-0 rounded-full transition duration-220 ease-matex focus:outline-none focus:ring-2 focus:ring-brand/40 ${
                    isItem ? 'bg-brand' : 'bg-line'
                }`}
            >
                <span
                    className={`absolute top-0.5 h-6 w-6 rounded-full bg-white transition duration-220 ease-matex ${
                        isItem ? 'left-5' : 'left-0.5'
                    }`}
                />
            </button>
            <span
                className={`text-sm ${isItem ? 'font-semibold text-ink' : 'text-ink-muted'}`}
            >
                Item Number
            </span>
        </div>
    );
}

export default function Dashboard({
    stats,
    recentPos,
    itemRows,
    mode = 'po',
    filters,
    roleLabel,
}) {
    const [openItemId, setOpenItemId] = useState(null);
    const [searchDraft, setSearchDraft] = useState(filters?.search || '');

    const setMode = (next) => {
        router.get(
            route('dashboard'),
            {
                mode: next,
                search: next === 'item' ? filters?.search || undefined : undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const applySearch = (value) => {
        router.get(
            route('dashboard'),
            { mode: 'item', search: value || undefined },
            { preserveState: true, replace: true },
        );
    };

    const items = itemRows?.data || [];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="ui-eyebrow">Overview</p>
                        <h2 className="ui-section-title mt-1">Dashboard</h2>
                        <p className="mt-1 text-sm text-ink-muted">
                            Portal Matex — {roleLabel}
                        </p>
                    </div>
                    <ModeToggle mode={mode} onChange={setMode} />
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="ui-page space-y-6">
                <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                    {Object.entries(stats || {}).map(([key, value], index) => (
                        <StatCard
                            key={key}
                            label={key.replaceAll('_', ' ')}
                            value={value}
                            index={index}
                        />
                    ))}
                </div>

                {mode === 'po' ? (
                    <div className="ui-panel animate-fade-up overflow-hidden">
                        <div className="flex items-center justify-between border-b border-line px-5 py-4">
                            <h3 className="font-display text-base font-semibold text-ink">
                                PO Terbaru
                            </h3>
                            <Link
                                href={route('purchase-orders.index')}
                                className="ui-link text-sm"
                            >
                                Lihat semua
                            </Link>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-line text-sm">
                                <thead className="ui-table-head">
                                    <tr>
                                        <th className="px-5 py-3">PO Number</th>
                                        <th className="px-5 py-3">Supplier RM</th>
                                        <th className="px-5 py-3">OHP</th>
                                        <th className="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {recentPos.map((po) => (
                                        <tr
                                            key={po.id}
                                            className="ui-row cursor-pointer"
                                            title="Klik baris untuk melihat detail PO"
                                            onClick={() =>
                                                router.visit(
                                                    route('purchase-orders.show', po.id),
                                                )
                                            }
                                        >
                                            <td className="px-5 py-3 font-semibold text-brand">
                                                {po.po_number}
                                            </td>
                                            <td className="px-5 py-3 text-ink-soft">
                                                {po.supplier_rm?.name}
                                            </td>
                                            <td className="px-5 py-3 text-ink-soft">
                                                {ohpLabels(po) || '—'}
                                            </td>
                                            <td className="px-5 py-3">
                                                <div className="flex flex-wrap items-center gap-1.5">
                                                    <StatusBadge type="po" value={po.status} />
                                                    <StatusBadge
                                                        type="fulfillment"
                                                        value={po.is_closed ? 'closed' : 'open'}
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {!recentPos.length && (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="px-5 py-10 text-center text-ink-muted"
                                            >
                                                Belum ada data PO.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ) : (
                    <div className="ui-panel animate-fade-up overflow-hidden">
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                            <div>
                                <h3 className="font-display text-base font-semibold text-ink">
                                    PO Berjalan per Item Number
                                </h3>
                                <p className="mt-0.5 text-xs text-ink-muted">
                                    Klik item untuk melihat PO yang sedang berjalan, lalu klik PO
                                    untuk membuka detail.
                                </p>
                            </div>
                            <form
                                className="flex gap-2"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    applySearch(searchDraft);
                                }}
                            >
                                <input
                                    type="search"
                                    placeholder="Cari item number..."
                                    className="rounded-xl border-line text-sm"
                                    value={searchDraft}
                                    onChange={(e) => setSearchDraft(e.target.value)}
                                />
                                <button
                                    type="submit"
                                    className="pressable rounded-xl bg-brand px-3 py-2 text-sm font-semibold text-white"
                                >
                                    Cari
                                </button>
                            </form>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-line text-sm">
                                <thead className="ui-table-head">
                                    <tr>
                                        <th className="px-5 py-3">Item Number</th>
                                        <th className="px-5 py-3">Description</th>
                                        <th className="px-5 py-3">PO Berjalan</th>
                                        <th className="px-5 py-3">Total Qty</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {items.map((item) => {
                                        const open = String(openItemId) === String(item.id);
                                        return (
                                            <Fragment key={item.id}>
                                                <tr
                                                    className={`ui-row cursor-pointer ${open ? 'bg-brand-muted/40' : ''}`}
                                                    onClick={() =>
                                                        setOpenItemId(open ? null : item.id)
                                                    }
                                                >
                                                    <td className="px-5 py-3 font-semibold text-brand">
                                                        {item.item_number}
                                                    </td>
                                                    <td className="px-5 py-3 text-ink-soft">
                                                        {item.description}
                                                    </td>
                                                    <td className="px-5 py-3 tabular-nums text-ink-soft">
                                                        {item.running_po_count}
                                                    </td>
                                                    <td className="px-5 py-3 tabular-nums text-ink-soft">
                                                        {formatQty(item.total_qty)} kg
                                                    </td>
                                                </tr>
                                                {open && (
                                                    <tr className="bg-canvas-soft/70">
                                                        <td colSpan={4} className="px-5 py-4">
                                                            <h4 className="font-display text-sm font-semibold text-ink">
                                                                PO berjalan untuk {item.item_number}
                                                            </h4>
                                                            <div className="mt-3 overflow-x-auto rounded-xl border border-line bg-surface">
                                                                <table className="min-w-full divide-y divide-line text-sm">
                                                                    <thead className="ui-table-head">
                                                                        <tr>
                                                                            <th className="px-4 py-2">
                                                                                PO Number
                                                                            </th>
                                                                            <th className="px-4 py-2">
                                                                                Supplier RM
                                                                            </th>
                                                                            <th className="px-4 py-2">
                                                                                Qty
                                                                            </th>
                                                                            <th className="px-4 py-2">
                                                                                Status
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody className="divide-y divide-line">
                                                                        {item.purchase_orders.map(
                                                                            (po) => (
                                                                                <tr
                                                                                    key={po.id}
                                                                                    className="ui-row cursor-pointer"
                                                                                    onClick={(e) => {
                                                                                        e.stopPropagation();
                                                                                        router.visit(
                                                                                            route(
                                                                                                'purchase-orders.show',
                                                                                                po.id,
                                                                                            ),
                                                                                        );
                                                                                    }}
                                                                                >
                                                                                    <td className="px-4 py-2 font-semibold text-brand">
                                                                                        {po.po_number}
                                                                                    </td>
                                                                                    <td className="px-4 py-2 text-ink-soft">
                                                                                        {po.supplier_rm
                                                                                            ?.name ||
                                                                                            '—'}
                                                                                    </td>
                                                                                    <td className="px-4 py-2 tabular-nums text-ink-soft">
                                                                                        {formatQty(
                                                                                            po.qty,
                                                                                        )}{' '}
                                                                                        kg
                                                                                    </td>
                                                                                    <td className="px-4 py-2">
                                                                                        <div className="flex flex-wrap items-center gap-1.5">
                                                                                            <StatusBadge
                                                                                                type="po"
                                                                                                value={
                                                                                                    po.status
                                                                                                }
                                                                                            />
                                                                                            <StatusBadge
                                                                                                type="fulfillment"
                                                                                                value={
                                                                                                    po.is_closed
                                                                                                        ? 'closed'
                                                                                                        : 'open'
                                                                                                }
                                                                                            />
                                                                                        </div>
                                                                                    </td>
                                                                                </tr>
                                                                            ),
                                                                        )}
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                )}
                                            </Fragment>
                                        );
                                    })}
                                    {!items.length && (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="px-5 py-10 text-center text-ink-muted"
                                            >
                                                Tidak ada item dengan PO berjalan
                                                {filters?.search ? ` untuk “${filters.search}”` : '.'}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Pagination paginator={itemRows} />
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
