import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import StatusBadge from '@/Components/StatusBadge';
import TextInput from '@/Components/TextInput';
import Timeline from '@/Components/Timeline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatQty, isQtyInput, roundQty, toNum } from '@/utils/qty';
import { encodeUploadField, formatFileSize } from '@/utils/upload';
import {
    isWeekendDay,
    weekendCellClass,
    weekendHeaderClass,
    weekendShortLabel,
    weekdayLabel,
} from '@/utils/calendar';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

function pad2(n) {
    return String(n).padStart(2, '0');
}

function qtyChangeBadge(ordered, confirmed) {
    if (confirmed === null || confirmed === undefined || confirmed === '') {
        return null;
    }
    const diff = roundQty(toNum(confirmed) - toNum(ordered));
    if (diff === 0) {
        return {
            label: 'OK',
            hint: 'Qty sama dengan order',
            className: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        };
    }
    if (diff < 0) {
        return {
            label: 'Minus',
            hint: `${diff} kg dari order`,
            className: 'bg-amber-100 text-amber-900 border-amber-200',
        };
    }
    return {
        label: 'Over',
        hint: `+${diff} kg dari order`,
        className: 'bg-rose-100 text-rose-800 border-rose-200',
    };
}

function ChangeBadge({ ordered, confirmed }) {
    const badge = qtyChangeBadge(ordered, confirmed);
    if (!badge) {
        return <span className="text-ink-faint">—</span>;
    }
    return (
        <span
            className={`inline-flex flex-col rounded-md border px-2 py-1 text-xs font-semibold ${badge.className}`}
            title={badge.hint}
        >
            <span>{badge.label}</span>
            <span className="font-normal opacity-80">{badge.hint}</span>
        </span>
    );
}

function parseDueMonth(dueDate) {
    if (!dueDate) {
        return null;
    }
    const raw = String(dueDate).slice(0, 10);
    if (!/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        return null;
    }
    const [year, month] = raw.split('-').map(Number);
    const days = new Date(year, month, 0).getDate();
    return { year, month, days };
}

function dateForDay(dueMonth, day) {
    return `${dueMonth.year}-${pad2(dueMonth.month)}-${pad2(day)}`;
}

function monthTitle(dueMonth) {
    return new Date(dueMonth.year, dueMonth.month - 1, 1).toLocaleDateString('id-ID', {
        month: 'long',
        year: 'numeric',
    });
}

function dayOfDate(dateStr) {
    if (!dateStr) {
        return null;
    }
    const day = Number(String(dateStr).slice(8, 10));
    return Number.isFinite(day) ? day : null;
}

/**
 * Draft awal konfirmasi RM: { [itemId]: { [day]: 'qty' } } dari jadwal yang
 * tersimpan (qty_confirmed terakhir, atau rencana Purchasing).
 */
function initialCellDrafts(order, dueMonth) {
    const cells = {};
    if (!dueMonth) {
        return cells;
    }
    (order.schedules || []).forEach((s) => {
        const day = dayOfDate(s.scheduled_date);
        if (!day || day < 1 || day > dueMonth.days) {
            return;
        }
        const itemId = String(s.purchase_order_item_id);
        const qty = toNum(s.qty_confirmed ?? s.qty);
        cells[itemId] = cells[itemId] || {};
        cells[itemId][day] = String(roundQty(toNum(cells[itemId][day]) + qty));
    });
    return cells;
}

function cellDraftsToSchedules(cells, dueMonth) {
    const out = [];
    Object.entries(cells).forEach(([itemId, days]) => {
        Object.entries(days).forEach(([day, qty]) => {
            if (qty === '' || qty === null || qty === undefined) {
                return;
            }
            out.push({
                purchase_order_item_id: Number(itemId),
                scheduled_date: dateForDay(dueMonth, Number(day)),
                qty_confirmed: toNum(qty),
            });
        });
    });
    return out;
}

function itemDraftTotal(cells, itemId) {
    return roundQty(
        Object.values(cells?.[String(itemId)] || {}).reduce((sum, v) => sum + toNum(v), 0),
    );
}

/**
 * Matrix jadwal: baris = part, kolom = tanggal 1–N bulan due date.
 * mode: 'edit' | 'review' | 'view'
 * Di mode edit, SEMUA cell tanggal bisa diisi RM (mix & match tanggal/qty);
 * cellDrafts = { [itemId]: { [day]: 'qty' } }.
 */
function ScheduleMatrix({
    order,
    dueMonth,
    mode,
    cellDrafts,
    onChangeQty,
    showRmChanges,
    hideInternalHistory,
}) {
    const dayColumns = Array.from({ length: dueMonth.days }, (_, i) => i + 1);

    const rows = useMemo(() => {
        return (order.items || []).map((item) => {
            const itemSchedules = (order.schedules || []).filter(
                (s) => String(s.purchase_order_item_id) === String(item.id),
            );
            const byDay = {};
            itemSchedules.forEach((s) => {
                const day = dayOfDate(s.scheduled_date);
                if (!day || day < 1 || day > dueMonth.days) {
                    return;
                }
                if (!byDay[day]) {
                    byDay[day] = s;
                }
            });

            const plannedTotal = roundQty(
                itemSchedules.reduce((sum, s) => sum + toNum(s.qty), 0),
            );

            const confirmedTotal =
                mode === 'edit'
                    ? itemDraftTotal(cellDrafts, item.id)
                    : roundQty(
                          itemSchedules.reduce((sum, s) => sum + toNum(s.qty_confirmed), 0),
                      );

            const ohpName =
                itemSchedules[0]?.ohp_supplier?.name ||
                item.item?.subcont_ohp?.name ||
                '—';

            return {
                item,
                byDay,
                itemSchedules,
                plannedTotal,
                confirmedTotal,
                ohpName,
            };
        });
    }, [order.items, order.schedules, dueMonth, cellDrafts, mode]);

    if (rows.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-line bg-canvas-soft px-4 py-8 text-center text-sm text-ink-muted">
                Belum ada item / jadwal pengiriman.
            </div>
        );
    }

    return (
        <div className="-mx-1 overflow-x-auto rounded-lg border border-line">
            <table className="min-w-full border-collapse text-sm">
                <thead>
                    <tr className="bg-canvas text-ink-soft">
                        <th className="sticky left-0 z-20 w-[200px] min-w-[200px] max-w-[200px] border-b border-r border-line bg-canvas px-3 py-2 text-left font-semibold shadow-[2px_0_4px_-2px_rgba(0,0,0,0.08)]">
                            Part
                        </th>
                        <th className="sticky left-[200px] z-20 w-[160px] min-w-[160px] max-w-[160px] border-b border-r border-line bg-canvas px-2 py-2 text-left font-semibold shadow-[2px_0_4px_-2px_rgba(0,0,0,0.08)]">
                            OHP
                        </th>
                        {!hideInternalHistory && (
                            <th className="min-w-[80px] border-b border-r border-line px-2 py-2 text-right font-semibold">
                                Order
                            </th>
                        )}
                        {!hideInternalHistory && (
                            <th className="min-w-[80px] border-b border-r border-line px-2 py-2 text-right font-semibold">
                                Rencana
                            </th>
                        )}
                        <th className="min-w-[88px] border-b border-r border-line px-2 py-2 text-right font-semibold">
                            {hideInternalHistory ? 'Qty' : 'Confirm'}
                        </th>
                        {showRmChanges && !hideInternalHistory && (
                            <th className="min-w-[90px] border-b border-r border-line px-2 py-2 text-center font-semibold">
                                Status
                            </th>
                        )}
                        {dayColumns.map((day) => {
                            const sabMin = weekendShortLabel(dueMonth, day);
                            return (
                            <th
                                key={day}
                                title={weekdayLabel(dueMonth, day)}
                                className={`${mode === 'edit' ? 'min-w-[92px]' : 'min-w-[52px]'} border-b border-line px-1 py-2 text-center font-semibold tabular-nums ${weekendHeaderClass(dueMonth, day)}`}
                            >
                                {day}
                                {sabMin && (
                                    <div className="text-[9px] font-semibold leading-none tracking-wide">
                                        {sabMin}
                                    </div>
                                )}
                            </th>
                            );
                        })}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row, rowIdx) => {
                        const ordered = toNum(row.item.qty_ordered);
                        const zebra = rowIdx % 2 === 0 ? 'bg-surface' : 'bg-canvas-soft/80';
                        const displayConfirmed =
                            mode === 'edit'
                                ? row.confirmedTotal
                                : toNum(row.item.qty_confirmed ?? row.confirmedTotal);

                        return (
                            <tr key={row.item.id} className={zebra}>
                                <td
                                    className={`sticky left-0 z-10 w-[200px] min-w-[200px] max-w-[200px] border-b border-r border-line px-3 py-2 align-middle shadow-[2px_0_4px_-2px_rgba(0,0,0,0.06)] ${zebra}`}
                                >
                                    <div className="font-medium text-ink">
                                        {row.item.item?.item_number}
                                    </div>
                                    <div className="mt-0.5 max-w-[176px] truncate text-xs text-ink-muted">
                                        {row.item.item?.description}
                                    </div>
                                </td>
                                <td
                                    className={`sticky left-[200px] z-10 w-[160px] min-w-[160px] max-w-[160px] border-b border-r border-line px-2 py-2 align-middle text-xs text-ink-soft shadow-[2px_0_4px_-2px_rgba(0,0,0,0.06)] ${zebra}`}
                                >
                                    <span className="line-clamp-2">{row.ohpName}</span>
                                </td>
                                {!hideInternalHistory && (
                                    <td className="border-b border-r border-line px-2 py-2 text-right tabular-nums text-ink-soft">
                                        {formatQty(ordered)}
                                    </td>
                                )}
                                {!hideInternalHistory && (
                                    <td className="border-b border-r border-line px-2 py-2 text-right tabular-nums text-ink-soft">
                                        {formatQty(row.plannedTotal)}
                                    </td>
                                )}
                                <td className="border-b border-r border-line px-2 py-2 text-right font-semibold tabular-nums text-ink">
                                    {formatQty(
                                        mode === 'edit' ? displayConfirmed : displayConfirmed || ordered,
                                    )}
                                </td>
                                {showRmChanges && !hideInternalHistory && (
                                    <td className="border-b border-r border-line px-2 py-2 text-center">
                                        <ChangeBadge
                                            ordered={ordered}
                                            confirmed={
                                                mode === 'edit'
                                                    ? row.confirmedTotal
                                                    : (row.item.qty_confirmed ?? row.confirmedTotal)
                                            }
                                        />
                                    </td>
                                )}
                                {dayColumns.map((day) => {
                                    const schedule = row.byDay[day];
                                    const weekend = isWeekendDay(dueMonth, day);
                                    const weekendWash = weekendCellClass(dueMonth, day);

                                    if (mode === 'edit') {
                                        const planned = toNum(schedule?.qty);
                                        const rawVal = cellDrafts?.[String(row.item.id)]?.[day];
                                        const confirmedVal =
                                            rawVal === undefined || rawVal === null ? '' : String(rawVal);
                                        const confirmedNum = toNum(confirmedVal);
                                        const filled = confirmedNum > 0;
                                        const changed = roundQty(confirmedNum) !== roundQty(planned);
                                        // Plan Purchasing yang dikosongkan RM: tampil sebagai
                                        // angka dicoret + garis putus-putus, jelas bukan value aktual.
                                        const droppedPlan = !filled && planned > 0;
                                        const inputTone = droppedPlan
                                            ? 'border-dashed border-amber-300 bg-transparent text-ink placeholder:text-ink-faint placeholder:line-through'
                                            : filled && changed
                                              ? 'border-amber-300 bg-amber-50 font-medium text-amber-950'
                                              : filled
                                                ? weekend
                                                    ? 'border-rose-300 bg-rose-100 font-medium text-rose-900'
                                                    : 'border-brand-line bg-brand-muted font-medium text-brand-deep'
                                                : weekend
                                                  ? 'border-transparent bg-rose-50/60 text-ink hover:border-line'
                                                  : 'border-transparent bg-transparent text-ink hover:border-line';
                                        return (
                                            <td
                                                key={day}
                                                title={
                                                    droppedPlan
                                                        ? `${weekdayLabel(dueMonth, day)} · Rencana ${formatQty(planned)} kg — dikosongkan`
                                                        : planned > 0
                                                          ? `${weekdayLabel(dueMonth, day)} · Rencana ${formatQty(planned)} kg`
                                                          : weekdayLabel(dueMonth, day)
                                                }
                                                className={`border-b border-line p-0.5 align-middle ${weekendWash}`}
                                            >
                                                <input
                                                    type="text"
                                                    inputMode="numeric"
                                                    autoComplete="off"
                                                    aria-label={`Qty confirm ${row.item.item?.item_number} tgl ${day}`}
                                                    placeholder={planned > 0 ? formatQty(planned) : ''}
                                                    className={`no-spin w-full rounded border px-1 py-1.5 text-center text-xs tabular-nums focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand ${inputTone}`}
                                                    value={confirmedVal}
                                                    onChange={(e) => {
                                                        if (isQtyInput(e.target.value)) {
                                                            onChangeQty(row.item.id, day, e.target.value);
                                                        }
                                                    }}
                                                    onFocus={(e) => e.target.select()}
                                                />
                                            </td>
                                        );
                                    }

                                    if (!schedule) {
                                        return (
                                            <td
                                                key={day}
                                                title={weekdayLabel(dueMonth, day)}
                                                className={`border-b border-line px-1 py-2 text-center ${
                                                    weekend ? 'bg-rose-50 text-rose-300' : 'text-slate-300'
                                                }`}
                                            >
                                                ·
                                            </td>
                                        );
                                    }

                                    const planned = toNum(schedule.qty);
                                    const confirmedNum = toNum(schedule.qty_confirmed);
                                    const cellBadge = showRmChanges
                                        ? qtyChangeBadge(planned, schedule.qty_confirmed)
                                        : null;

                                    const tone =
                                        cellBadge?.label === 'OK'
                                            ? 'bg-emerald-50 text-emerald-900'
                                            : cellBadge?.label === 'Minus'
                                              ? 'bg-amber-50 text-amber-950'
                                              : cellBadge?.label === 'Over'
                                                ? 'bg-rose-50 text-rose-900'
                                                : confirmedNum > 0
                                                  ? weekend
                                                    ? 'bg-rose-100 text-rose-900'
                                                    : 'bg-brand-muted/80 text-brand-deep'
                                                  : weekend
                                                    ? 'bg-rose-50 text-ink-muted'
                                                    : 'text-ink-muted';

                                    return (
                                        <td
                                            key={day}
                                            className={`border-b border-line px-1 py-1.5 text-center align-middle ${tone}`}
                                            title={
                                                !hideInternalHistory
                                                    ? `${weekdayLabel(dueMonth, day)} · Rencana ${formatQty(planned)} kg`
                                                    : weekdayLabel(dueMonth, day)
                                            }
                                        >
                                            <div className="text-xs font-semibold tabular-nums">
                                                {confirmedNum > 0 || schedule.qty_confirmed != null
                                                    ? formatQty(
                                                          schedule.qty_confirmed ?? confirmedNum,
                                                      )
                                                    : formatQty(planned)}
                                            </div>
                                            {!hideInternalHistory &&
                                                showRmChanges &&
                                                schedule.qty_confirmed != null &&
                                                roundQty(toNum(schedule.qty_confirmed)) !==
                                                    roundQty(planned) && (
                                                    <div className="text-[10px] tabular-nums opacity-70">
                                                        plan {formatQty(planned)}
                                                    </div>
                                                )}
                                        </td>
                                    );
                                })}
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

export default function Show({ order, hideInternalHistory = false }) {
    const { auth } = usePage().props;
    const role = auth.user.role;
    const [rejectReason, setRejectReason] = useState('');
    const [dnDrafts, setDnDrafts] = useState(() => {
        const initial = {};
        (order.schedules || []).forEach((s) => {
            initial[s.id] = {
                rm_sj_number: s.rm_sj_number || '',
                delivery_date: s.scheduled_date?.slice(0, 10) || '',
            };
        });
        return initial;
    });

    const dueMonth = parseDueMonth(order.due_date);

    // Draft konfirmasi RM per cell (item × tanggal). RM bebas mix & match:
    // isi tanggal baru, kosongkan tanggal rencana, ubah qty — Purchasing yang
    // menilai lagi lewat badge OK / Minus / Over saat approve.
    const rmForm = useForm({ cells: initialCellDrafts(order, dueMonth) });

    const canEdit = role === 'purchasing' || role === 'admin';
    const canSubmit = canEdit && order.status === 'draft';
    const canConfirmRm = role === 'supplier_rm' && order.status === 'awaiting_rm_confirm';
    const canApprove = canEdit && order.status === 'awaiting_purchasing_ok';
    const canGenerateDn =
        role === 'supplier_rm' && ['confirmed', 'in_progress'].includes(order.status);
    const showRmChanges = ['awaiting_purchasing_ok', 'confirmed', 'in_progress', 'completed'].includes(
        order.status,
    );
    const signedPoRelevant = ['confirmed', 'in_progress', 'completed'].includes(order.status);
    const canUploadSignedPo = canEdit && signedPoRelevant;

    const SIGNED_PO_MAX_MB = 10;
    // File dikirim sebagai base64 (lihat utils/upload.js) agar tidak diblokir WAF.
    const signedPoForm = useForm({ signed_po_base64: null, signed_po_name: null });
    const [signedPoEncoding, setSignedPoEncoding] = useState(false);
    const pickSignedPo = async (file) => {
        signedPoForm.clearErrors('signed_po');
        if (!file) {
            signedPoForm.setData({ signed_po_base64: null, signed_po_name: null });
            return;
        }
        const isPdf =
            file.type === 'application/pdf' || /\.pdf$/i.test(file.name || '');
        if (!isPdf) {
            signedPoForm.setData({ signed_po_base64: null, signed_po_name: null });
            signedPoForm.setError('signed_po', 'File harus berformat PDF.');
            return;
        }
        if (file.size > SIGNED_PO_MAX_MB * 1024 * 1024) {
            signedPoForm.setData({ signed_po_base64: null, signed_po_name: null });
            signedPoForm.setError(
                'signed_po',
                `Ukuran file ${formatFileSize(file.size)} melebihi batas ${SIGNED_PO_MAX_MB} MB.`,
            );
            return;
        }
        setSignedPoEncoding(true);
        try {
            signedPoForm.setData(await encodeUploadField('signed_po', file));
        } catch (err) {
            signedPoForm.setError('signed_po', err?.message || 'Gagal membaca file.');
        } finally {
            setSignedPoEncoding(false);
        }
    };
    // Respons non-Inertia (mis. HTML 403 dari WAF / 413 dari proxy) secara
    // default ditampilkan Inertia sebagai modal iframe yang terlihat seperti
    // "loading terus". Tangkap dan ubah jadi pesan error yang jelas.
    const uploadingSignedPo = useRef(false);
    const [signedPoGatewayError, setSignedPoGatewayError] = useState(null);
    useEffect(
        () =>
            router.on('invalid', (event) => {
                if (!uploadingSignedPo.current) {
                    return;
                }
                event.preventDefault();
                const status = event.detail?.response?.status;
                setSignedPoGatewayError(
                    status === 403
                        ? 'Upload ditolak oleh firewall (HTTP 403) sebelum sampai ke server Matex. Hubungi admin.'
                        : status === 413
                          ? 'Ukuran file ditolak oleh server/proxy (HTTP 413).'
                          : `Upload gagal di gateway (HTTP ${status ?? '?'}). Coba lagi atau hubungi admin.`,
                );
            }),
        [],
    );

    const submitSignedPo = (e) => {
        e.preventDefault();
        if (!signedPoForm.data.signed_po_base64) {
            return;
        }
        setSignedPoGatewayError(null);
        uploadingSignedPo.current = true;
        signedPoForm.post(route('purchase-orders.upload-signed-po', order.id), {
            preserveScroll: true,
            onSuccess: () => signedPoForm.reset(),
            onFinish: () => {
                uploadingSignedPo.current = false;
            },
        });
    };

    const matrixMode = canConfirmRm ? 'edit' : showRmChanges || canApprove ? 'review' : 'view';

    const pendingDnSchedules = useMemo(
        () => (order.schedules || []).filter((s) => !s.delivery_note),
        [order.schedules],
    );

    const updateCellQty = (itemId, day, qty) => {
        rmForm.setData((data) => {
            const key = String(itemId);
            const itemCells = { ...(data.cells[key] || {}) };
            if (qty === '' || toNum(qty) === 0) {
                delete itemCells[day];
            } else {
                itemCells[day] = qty;
            }
            return { ...data, cells: { ...data.cells, [key]: itemCells } };
        });
    };

    const rmItemsWithoutQty = useMemo(
        () =>
            canConfirmRm
                ? order.items.filter((item) => itemDraftTotal(rmForm.data.cells, item.id) <= 0)
                : [],
        [canConfirmRm, order.items, rmForm.data.cells],
    );

    const submitRmConfirmation = () => {
        if (!dueMonth) {
            return;
        }
        rmForm.transform((data) => ({
            schedules: cellDraftsToSchedules(data.cells, dueMonth),
        }));
        rmForm.post(route('purchase-orders.confirm-rm', order.id), { preserveScroll: true });
    };

    const rmErrorMessages = useMemo(
        () => Array.from(new Set(Object.values(rmForm.errors || {}))),
        [rmForm.errors],
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 className="text-xl font-semibold text-ink">
                            {order.po_number}
                        </h2>
                        <div className="mt-2 flex flex-wrap items-center gap-2">
                            <StatusBadge type="po" value={order.status} />
                            <StatusBadge
                                type="fulfillment"
                                value={order.is_closed ? 'closed' : 'open'}
                                title={
                                    order.is_closed
                                        ? 'Seluruh DN sudah dikirim dan sudah di-approve'
                                        : 'Masih ada DN yang belum dikirim atau belum di-approve'
                                }
                            />
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canEdit && order.status === 'draft' && (
                            <Link
                                href={route('purchase-orders.edit', order.id)}
                                className="rounded-md border border-line px-3 py-2 text-sm"
                            >
                                Edit Draft
                            </Link>
                        )}
                        {canSubmit && (
                            <PrimaryButton
                                className="bg-brand"
                                onClick={() =>
                                    router.post(route('purchase-orders.submit', order.id))
                                }
                            >
                                Submit ke RM
                            </PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`PO ${order.po_number}`} />

            <div className="ui-page">
                <div className="grid w-full gap-4 lg:grid-cols-[1fr_280px]">
                    <div className="min-w-0 space-y-4">
                        <section className="ui-panel animate-fade-up p-5">
                            <h3 className="mb-4 font-display font-semibold text-ink">Informasi PO</h3>
                            <dl className="grid gap-3 text-sm sm:grid-cols-3">
                                <div>
                                    <dt className="text-ink-muted">Supplier RM</dt>
                                    <dd className="font-medium">{order.supplier_rm?.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-ink-muted">Due Date</dt>
                                    <dd className="font-medium">
                                        {new Date(order.due_date).toLocaleDateString('id-ID')}
                                        {dueMonth && (
                                            <span className="ml-2 text-xs font-normal text-ink-muted">
                                                ({monthTitle(dueMonth)})
                                            </span>
                                        )}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-ink-muted">Dibuat oleh</dt>
                                    <dd className="font-medium">{order.creator?.name}</dd>
                                </div>
                            </dl>
                            {!hideInternalHistory && order.rejection_reason && (
                                <div className="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                                    Alasan reject: {order.rejection_reason}
                                </div>
                            )}

                            {!hideInternalHistory && signedPoRelevant && (
                                <div
                                    className={`mt-4 rounded-lg border p-3 text-sm ${
                                        order.has_signed_po
                                            ? 'border-brand-line bg-brand-muted text-brand-deep'
                                            : 'border-amber-200 bg-amber-50 text-amber-900'
                                    }`}
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p className="font-semibold">
                                                {order.has_signed_po
                                                    ? 'Dokumen PO sudah diunggah'
                                                    : 'Dokumen PO (belum diunggah)'}
                                            </p>
                                            {order.has_signed_po && (
                                                <p className="mt-0.5 text-xs opacity-80">
                                                    {new Date(
                                                        order.signed_po_uploaded_at,
                                                    ).toLocaleString('id-ID')}{' '}
                                                    oleh {order.signed_po_uploader?.name || '—'}
                                                </p>
                                            )}
                                        </div>
                                        {order.has_signed_po && (
                                            <a
                                                href={route(
                                                    'purchase-orders.download-signed-po',
                                                    order.id,
                                                )}
                                                className="rounded-md border border-brand-line bg-surface px-3 py-1.5 text-xs font-semibold text-brand hover:bg-brand-muted"
                                            >
                                                Unduh Signed PO
                                            </a>
                                        )}
                                    </div>

                                    {canUploadSignedPo && (
                                        <form
                                            onSubmit={submitSignedPo}
                                            className="mt-3 flex flex-wrap items-center gap-2"
                                        >
                                            <input
                                                type="file"
                                                accept="application/pdf,.pdf"
                                                className="text-xs"
                                                onChange={(e) =>
                                                    pickSignedPo(e.target.files?.[0] || null)
                                                }
                                            />
                                            <PrimaryButton
                                                className="bg-brand py-1.5 text-xs"
                                                disabled={
                                                    signedPoForm.processing ||
                                                    signedPoEncoding ||
                                                    !signedPoForm.data.signed_po_base64
                                                }
                                            >
                                                {signedPoEncoding
                                                    ? 'Membaca file…'
                                                    : signedPoForm.processing
                                                      ? `Mengunggah… ${signedPoForm.progress?.percentage ?? 0}%`
                                                      : order.has_signed_po
                                                      ? 'Ganti File'
                                                      : 'Unggah Signed PO'}
                                            </PrimaryButton>
                                            <span className="text-xs opacity-70">
                                                PDF, maks. {SIGNED_PO_MAX_MB} MB
                                            </span>
                                            <InputError
                                                message={
                                                    signedPoForm.errors.signed_po ||
                                                    signedPoGatewayError
                                                }
                                                className="w-full text-xs"
                                            />
                                        </form>
                                    )}
                                </div>
                            )}
                        </section>

                        <section className="ui-panel p-5">
                            <div className="mb-1 flex flex-wrap items-center justify-between gap-3">
                                <h3 className="font-semibold text-ink">
                                    {canConfirmRm
                                        ? 'Konfirmasi Jadwal Pengiriman'
                                        : canApprove
                                          ? 'Review Jadwal & Konfirmasi RM'
                                          : 'Jadwal Pengiriman'}
                                </h3>
                                {dueMonth && (
                                    <span className="rounded-md bg-brand-muted px-3 py-1 text-sm font-medium text-brand-deep">
                                        {monthTitle(dueMonth)} · tgl 1–{dueMonth.days}
                                    </span>
                                )}
                            </div>
                            <p className="mb-4 text-sm text-ink-muted">
                                {canConfirmRm
                                    ? 'Isi qty (kg) di tanggal mana pun dalam bulan due date — jadwal dari Purchasing hanya usulan awal, bebas dipindah, dipecah, atau diubah qty-nya. Cell kuning = beda dari rencana Purchasing. Kolom Status menunjukkan total per part sama / minus / over dari order.'
                                    : canApprove || showRmChanges
                                      ? 'Tabel perbandingan rencana vs konfirmasi RM. Hijau = OK, kuning = minus, merah = over.'
                                      : 'Alokasi qty per tanggal dalam bulan due date.'}
                            </p>

                            {dueMonth ? (
                                <ScheduleMatrix
                                    order={order}
                                    dueMonth={dueMonth}
                                    mode={matrixMode === 'edit' ? 'edit' : showRmChanges ? 'review' : 'view'}
                                    cellDrafts={canConfirmRm ? rmForm.data.cells : null}
                                    onChangeQty={updateCellQty}
                                    showRmChanges={showRmChanges || canConfirmRm}
                                    hideInternalHistory={hideInternalHistory}
                                />
                            ) : (
                                <div className="rounded-lg border border-dashed border-line bg-canvas-soft px-4 py-8 text-center text-sm text-ink-muted">
                                    Due date tidak valid — kolom tanggal tidak bisa ditampilkan.
                                </div>
                            )}

                            {canConfirmRm && (
                                <p className="mt-3 text-xs text-ink-muted">
                                    Tip: angka <span className="line-through">dicoret</span> dengan
                                    garis putus-putus = rencana Purchasing yang dikosongkan (bukan
                                    qty aktual). Kosongkan cell untuk membatalkan tanggal itu, isi
                                    cell lain untuk menambah tanggal kirim. Total Confirm per part
                                    dihitung otomatis dari semua cell berisi.
                                </p>
                            )}
                        </section>

                        {/* Detail DN / generate — tetap per jadwal */}
                        {(canGenerateDn ||
                            order.schedules.some((s) => s.delivery_note) ||
                            ['confirmed', 'in_progress', 'completed'].includes(order.status)) && (
                            <section className="ui-panel p-5">
                                <h3 className="mb-4 font-semibold text-ink">
                                    Delivery Note & Status Jadwal
                                </h3>
                                <div className="space-y-3">
                                    {order.schedules.map((schedule) => (
                                        <div
                                            key={schedule.id}
                                            className="rounded-lg border border-line p-4"
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <div>
                                                    <p className="font-medium text-ink">
                                                        {
                                                            schedule.purchase_order_item?.item
                                                                ?.item_number
                                                        }
                                                        <span className="ml-2 text-sm font-normal text-ink-muted">
                                                            {new Date(
                                                                schedule.scheduled_date,
                                                            ).toLocaleDateString('id-ID')}
                                                            {' · '}
                                                            {formatQty(
                                                                schedule.qty_confirmed ??
                                                                    schedule.qty,
                                                            )}{' '}
                                                            kg
                                                            {' · '}
                                                            OHP{' '}
                                                            {schedule.ohp_supplier?.name || '—'}
                                                        </span>
                                                    </p>
                                                    {!hideInternalHistory &&
                                                        schedule.rm_sj_number && (
                                                            <p className="mt-1 text-xs text-ink-soft">
                                                                SJ Internal RM:{' '}
                                                                {schedule.rm_sj_number}
                                                            </p>
                                                        )}
                                                    {hideInternalHistory &&
                                                        schedule.delivery_note?.rm_sj_number && (
                                                            <p className="mt-1 text-xs text-ink-soft">
                                                                Referensi DN:{' '}
                                                                {schedule.delivery_note.dn_number}
                                                            </p>
                                                        )}
                                                </div>
                                                <StatusBadge
                                                    type="schedule"
                                                    value={schedule.status}
                                                />
                                            </div>

                                            {canGenerateDn && !schedule.delivery_note && (
                                                <div className="mt-4 rounded-lg border border-brand-line bg-brand-muted p-3">
                                                    <div className="grid gap-3 sm:grid-cols-2">
                                                        <div>
                                                            <InputLabel value="No. Surat Jalan Internal RM" />
                                                            <TextInput
                                                                className="mt-1 w-full"
                                                                placeholder="Contoh: SJ-RM-2026-001"
                                                                value={
                                                                    dnDrafts[schedule.id]
                                                                        ?.rm_sj_number || ''
                                                                }
                                                                onChange={(e) =>
                                                                    setDnDrafts((prev) => ({
                                                                        ...prev,
                                                                        [schedule.id]: {
                                                                            ...prev[schedule.id],
                                                                            rm_sj_number:
                                                                                e.target.value,
                                                                        },
                                                                    }))
                                                                }
                                                            />
                                                        </div>
                                                        <div>
                                                            <InputLabel value="Tanggal Pengiriman (DN)" />
                                                            <TextInput
                                                                type="date"
                                                                className="mt-1 w-full"
                                                                value={
                                                                    dnDrafts[schedule.id]
                                                                        ?.delivery_date ||
                                                                    schedule.scheduled_date?.slice(
                                                                        0,
                                                                        10,
                                                                    ) ||
                                                                    ''
                                                                }
                                                                onChange={(e) =>
                                                                    setDnDrafts((prev) => ({
                                                                        ...prev,
                                                                        [schedule.id]: {
                                                                            ...prev[schedule.id],
                                                                            delivery_date:
                                                                                e.target.value,
                                                                        },
                                                                    }))
                                                                }
                                                            />
                                                            <p className="mt-1 text-xs text-brand-deep">
                                                                Default mengikuti plan{' '}
                                                                {schedule.scheduled_date
                                                                    ? new Date(
                                                                          schedule.scheduled_date,
                                                                      ).toLocaleDateString('id-ID')
                                                                    : '—'}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <PrimaryButton
                                                        className="mt-3 bg-brand whitespace-nowrap"
                                                        onClick={() =>
                                                            router.post(
                                                                route(
                                                                    'delivery-schedules.generate-dn',
                                                                    schedule.id,
                                                                ),
                                                                {
                                                                    rm_sj_number:
                                                                        dnDrafts[schedule.id]
                                                                            ?.rm_sj_number || '',
                                                                    delivery_date:
                                                                        dnDrafts[schedule.id]
                                                                            ?.delivery_date ||
                                                                        schedule.scheduled_date?.slice(
                                                                            0,
                                                                            10,
                                                                        ),
                                                                },
                                                                { preserveScroll: true },
                                                            )
                                                        }
                                                    >
                                                        Generate DN
                                                    </PrimaryButton>
                                                    <p className="mt-2 text-xs text-brand-deep">
                                                        Wajib isi nomor SJ internal. Setelah
                                                        generate, DN bisa di-reprint kapan saja.
                                                    </p>
                                                </div>
                                            )}

                                            {schedule.delivery_note && (
                                                <div className="mt-3 flex flex-wrap items-center gap-3 text-sm">
                                                    <Link
                                                        href={route(
                                                            'delivery-notes.show',
                                                            schedule.delivery_note.id,
                                                        )}
                                                        className="font-medium text-brand hover:underline"
                                                    >
                                                        {schedule.delivery_note.dn_number}
                                                    </Link>
                                                    {schedule.delivery_note.delivery_date && (
                                                        <span className="text-ink-muted">
                                                            Kirim{' '}
                                                            {new Date(
                                                                schedule.delivery_note.delivery_date,
                                                            ).toLocaleDateString('id-ID')}
                                                        </span>
                                                    )}
                                                    <a
                                                        href={route(
                                                            'delivery-notes.print',
                                                            schedule.delivery_note.id,
                                                        )}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="font-medium text-ink-soft hover:underline"
                                                    >
                                                        Reprint DN
                                                    </a>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>

                                {canGenerateDn && pendingDnSchedules.length > 0 && (
                                    <p className="mt-3 text-sm text-amber-800">
                                        Masih ada {pendingDnSchedules.length} jadwal yang belum punya
                                        DN. Isi no. SJ internal lalu generate DN.
                                    </p>
                                )}
                            </section>
                        )}

                        {canConfirmRm && (
                            <section className="rounded-xl border border-brand-line bg-brand-muted p-5">
                                <h3 className="font-semibold text-brand-deep">
                                    Konfirmasi Supplier RM
                                </h3>
                                <p className="mt-1 text-sm text-brand-deep">
                                    Pastikan qty di tabel sudah sesuai (bilangan bulat, tanpa koma).
                                    Setiap part minimal punya satu tanggal dengan qty.
                                </p>
                                {rmItemsWithoutQty.length > 0 && (
                                    <p className="mt-2 text-sm text-amber-800">
                                        Belum ada qty untuk:{' '}
                                        {rmItemsWithoutQty
                                            .map((item) => item.item?.item_number)
                                            .join(', ')}
                                    </p>
                                )}
                                <PrimaryButton
                                    className="mt-4 bg-brand"
                                    disabled={rmForm.processing || rmItemsWithoutQty.length > 0}
                                    onClick={submitRmConfirmation}
                                >
                                    Submit Konfirmasi
                                </PrimaryButton>
                                {rmErrorMessages.map((message) => (
                                    <InputError key={message} message={message} className="mt-2" />
                                ))}
                            </section>
                        )}

                        {canApprove && (
                            <section className="rounded-xl border border-amber-200 bg-amber-50 p-5">
                                <h3 className="font-semibold text-amber-900">
                                    Persetujuan Purchasing
                                </h3>
                                <p className="mt-1 text-sm text-amber-800">
                                    Review tabel di atas. Perhatikan highlight{' '}
                                    <strong>OK / Minus / Over</strong> per part dan per tanggal. Jika
                                    OK, Supplier RM akan mengisi no. SJ internal lalu generate DN.
                                </p>
                                <div className="mt-4 flex flex-wrap gap-3">
                                    <PrimaryButton
                                        className="bg-emerald-700 hover:bg-emerald-800"
                                        onClick={() =>
                                            router.post(route('purchase-orders.approve', order.id))
                                        }
                                    >
                                        Confirm OK
                                    </PrimaryButton>
                                </div>
                                <div className="mt-4 space-y-2">
                                    <InputLabel value="Alasan reject (jika ditolak)" />
                                    <textarea
                                        className="w-full rounded-md border-line"
                                        rows={2}
                                        value={rejectReason}
                                        onChange={(e) => setRejectReason(e.target.value)}
                                    />
                                    <button
                                        type="button"
                                        className="rounded-md border border-rose-300 bg-surface px-3 py-2 text-sm font-medium text-rose-700"
                                        onClick={() =>
                                            router.post(route('purchase-orders.reject', order.id), {
                                                reason: rejectReason,
                                            })
                                        }
                                    >
                                        Tolak & minta submit ulang
                                    </button>
                                </div>
                            </section>
                        )}
                    </div>

                    {!hideInternalHistory && (
                        <aside className="space-y-4">
                            <section className="ui-panel p-5">
                                <h3 className="mb-4 font-semibold text-ink">Timeline</h3>
                                <Timeline logs={order.status_logs || []} />
                            </section>
                        </aside>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
