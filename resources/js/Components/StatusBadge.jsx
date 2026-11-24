const COLORS = {
    slate: 'bg-canvas text-ink-soft ring-1 ring-inset ring-line',
    amber: 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200/70',
    orange: 'bg-copper-muted text-copper ring-1 ring-inset ring-copper-line',
    blue: 'bg-brand-muted text-brand-deep ring-1 ring-inset ring-brand-line',
    indigo: 'bg-sky-50 text-sky-800 ring-1 ring-inset ring-sky-200/70',
    emerald: 'bg-emerald-50 text-emerald-800 ring-1 ring-inset ring-emerald-200/70',
    rose: 'bg-rose-50 text-rose-800 ring-1 ring-inset ring-rose-200/70',
};

const PO_STATUS = {
    draft: { label: 'Draft', color: 'slate' },
    awaiting_rm_confirm: { label: 'Menunggu Konfirmasi RM', color: 'amber' },
    awaiting_purchasing_ok: { label: 'Menunggu OK Purchasing', color: 'orange' },
    confirmed: { label: 'Confirmed', color: 'blue' },
    in_progress: { label: 'Dalam Proses', color: 'indigo' },
    completed: { label: 'Selesai', color: 'emerald' },
    cancelled: { label: 'Dibatalkan', color: 'rose' },
};

const SCHEDULE_STATUS = {
    planned: { label: 'Terjadwal', color: 'slate' },
    ship_confirmed: { label: 'Dikirim ke OHP', color: 'amber' },
    ohp_ok: { label: 'OK OHP', color: 'blue' },
    received: { label: 'Received', color: 'emerald' },
};

const QAD_STATUS = {
    pending: { label: 'Pending', color: 'amber' },
    success: { label: 'QAD Sukses', color: 'emerald' },
    failed: { label: 'QAD Gagal', color: 'rose' },
};

const FULFILLMENT_STATUS = {
    open: { label: 'Open', color: 'amber' },
    closed: { label: 'Closed', color: 'emerald' },
};

export function resolveStatus(type, value) {
    const map =
        type === 'po'
            ? PO_STATUS
            : type === 'schedule'
              ? SCHEDULE_STATUS
              : type === 'fulfillment'
                ? FULFILLMENT_STATUS
                : QAD_STATUS;
    return map[value] || { label: value, color: 'slate' };
}

export default function StatusBadge({ type = 'po', value, className = '', title }) {
    const meta = resolveStatus(type, value);

    return (
        <span
            title={title}
            className={`inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-[0.6875rem] font-medium before:h-1.5 before:w-1.5 before:rounded-full before:bg-current before:opacity-70 before:content-[''] ${COLORS[meta.color]} ${className}`}
        >
            {meta.label}
        </span>
    );
}
