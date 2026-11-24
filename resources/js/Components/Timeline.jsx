import StatusBadge from '@/Components/StatusBadge';
import { useState } from 'react';

const ACTION_LABELS = {
    created: 'Draft dibuat',
    submitted: 'Dikirim ke Supplier RM',
    rm_confirmed: 'Dikonfirmasi Supplier RM',
    purchasing_rejected: 'Ditolak Purchasing',
    purchasing_approved: 'Confirm OK Purchasing',
    shipment_confirmed: 'DN dikirim ke OHP',
    ohp_confirmed: 'OK Supplier OHP',
    received: 'Received PPIC',
    receiving_failed: 'Receiving QAD gagal',
};

function actionTitle(action) {
    return ACTION_LABELS[action] || action;
}

function formatWhen(iso) {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function Timeline({ logs = [] }) {
    const [openId, setOpenId] = useState(null);

    if (!logs.length) {
        return <p className="text-sm text-ink-muted">Belum ada aktivitas tercatat.</p>;
    }

    return (
        <div>
            <p className="mb-3 text-xs text-ink-muted">Klik item untuk melihat detail.</p>
            <ol className="relative ms-3 space-y-3 border-s border-line">
                {logs.map((log, index) => {
                    const open = String(openId) === String(log.id);
                    const statusUnchanged = Boolean(log.from_status) && log.from_status === log.to_status;
                    const qadPo = log.meta?.qad_po_number;

                    return (
                        <li
                            key={log.id}
                            className="animate-fade-up ms-6"
                            style={{ animationDelay: `${Math.min(index, 6) * 40}ms` }}
                        >
                            <span
                                className={`absolute -start-1.5 mt-2 h-3 w-3 rounded-full border-2 border-surface ${
                                    open ? 'bg-brand ring-2 ring-brand/30' : 'bg-brand'
                                }`}
                            />
                            <div
                                className={`rounded-panel border bg-surface transition duration-220 ease-matex ${
                                    open
                                        ? 'border-brand-line shadow-panel'
                                        : 'border-line hover:border-brand-line hover:shadow-panel'
                                }`}
                            >
                                <button
                                    type="button"
                                    className="flex w-full items-start gap-2 p-3 text-left"
                                    aria-expanded={open}
                                    onClick={() => setOpenId(open ? null : log.id)}
                                >
                                    <span className="min-w-0 flex-1">
                                        <span className="block font-display text-sm font-semibold text-ink">
                                            {actionTitle(log.action)}
                                        </span>
                                        <time className="mt-0.5 block text-xs text-ink-muted">
                                            {formatWhen(log.created_at)}
                                        </time>
                                    </span>
                                    <span
                                        className={`mt-0.5 shrink-0 text-ink-faint transition ${open ? 'rotate-180' : ''}`}
                                        aria-hidden
                                    >
                                        ▾
                                    </span>
                                </button>
                                {open && (
                                    <div className="space-y-2 border-t border-line px-3 pb-3 pt-2">
                                        <div className="flex flex-wrap items-center gap-1.5 text-xs text-ink-muted">
                                            {statusUnchanged ? (
                                                <>
                                                    <span>Status tetap</span>
                                                    <StatusBadge type="po" value={log.to_status} />
                                                </>
                                            ) : (
                                                <>
                                                    {log.from_status ? (
                                                        <StatusBadge type="po" value={log.from_status} />
                                                    ) : (
                                                        <span>—</span>
                                                    )}
                                                    <span>→</span>
                                                    <StatusBadge type="po" value={log.to_status} />
                                                </>
                                            )}
                                        </div>
                                        {log.user?.name && (
                                            <p className="text-xs text-ink-muted">Oleh {log.user.name}</p>
                                        )}
                                        {qadPo && (
                                            <p className="text-xs text-ink-muted">
                                                Nomor QAD <span className="font-medium text-ink">{qadPo}</span>
                                            </p>
                                        )}
                                        {log.notes && (
                                            <p className="text-sm text-ink-soft">{log.notes}</p>
                                        )}
                                    </div>
                                )}
                            </div>
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}
