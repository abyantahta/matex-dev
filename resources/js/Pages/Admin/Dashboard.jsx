import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

const STAT_LABELS = {
    items_qad: 'Items QAD',
    supplier_rm: 'Supplier RM',
    supplier_ohp: 'Supplier OHP',
    purchase_orders: 'Purchase Orders',
    delivery_notes: 'Delivery Notes',
    users: 'Users',
};

const HERO_KEY = 'items_qad';

function statLabel(key) {
    return STAT_LABELS[key] ?? key.replaceAll('_', ' ');
}

function formatCount(value) {
    return Number(value ?? 0).toLocaleString('id-ID');
}

/* Ikon lucide (inline, 20px, stroke 2) agar tidak menambah dependensi. */
const ICONS = {
    database: (
        <>
            <ellipse cx="12" cy="5" rx="9" ry="3" />
            <path d="M3 5v14a9 3 0 0 0 18 0V5" />
            <path d="M3 12a9 3 0 0 0 18 0" />
        </>
    ),
    users: (
        <>
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </>
    ),
    calendarClock: (
        <>
            <path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5" />
            <path d="M16 2v4M8 2v4M3 10h5" />
            <circle cx="16" cy="16" r="6" />
            <path d="M16 14v2l1 1" />
        </>
    ),
    user: (
        <>
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7" r="4" />
        </>
    ),
    arrowRight: (
        <>
            <path d="M5 12h14" />
            <path d="m12 5 7 7-7 7" />
        </>
    ),
    grid: (
        <>
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
        </>
    ),
};

const TABLE_ICONS = {
    'qad-items': 'database',
    suppliers: 'users',
    discipline: 'calendarClock',
    users: 'user',
};

function Icon({ name, className = 'h-5 w-5' }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
            aria-hidden="true"
        >
            {ICONS[name] ?? ICONS.grid}
        </svg>
    );
}

function HeroStat({ value }) {
    return (
        <div className="ui-panel relative overflow-hidden border-0 bg-gradient-to-br from-brand-deep to-brand lg:col-span-2">
            <div className="relative z-10 flex min-h-[140px] flex-col justify-between p-6">
                <div>
                    <p className="font-display text-[0.6875rem] font-semibold uppercase tracking-[0.14em] text-white/80">
                        {statLabel(HERO_KEY)}
                    </p>
                    <p className="mt-3 font-display text-[2.75rem] font-bold leading-none tabular-nums text-white">
                        {formatCount(value)}
                    </p>
                </div>
                <p className="mt-4 text-xs text-brand-line">item master tersinkron dari QAD</p>
            </div>
            <svg
                viewBox="0 0 48 48"
                fill="currentColor"
                className="pointer-events-none absolute -bottom-6 -right-4 h-40 w-40 text-white opacity-[0.06]"
                aria-hidden="true"
            >
                <path d="M12 34V14h4.6l7.4 14.2L31.4 14H36v20h-3.8V21.2L25.4 34h-2.8L15.8 21.2V34H12Z" />
            </svg>
        </div>
    );
}

function StatStrip({ entries }) {
    return (
        <div className="ui-panel overflow-x-auto lg:col-span-4">
            <div className="flex h-full min-w-max divide-x divide-line">
                {entries.map(([key, value]) => {
                    const active = Number(value) > 0;
                    return (
                        <div
                            key={key}
                            className="flex min-w-[160px] flex-1 flex-col justify-center p-5 lg:p-6"
                        >
                            <div className="flex items-center gap-2">
                                <span
                                    className={
                                        'h-1.5 w-1.5 rounded-full ' +
                                        (active ? 'bg-brand-bright' : 'bg-ink-faint')
                                    }
                                />
                                <p className="ui-eyebrow">{statLabel(key)}</p>
                            </div>
                            <p className="mt-3 font-display text-3xl font-bold leading-tight tabular-nums text-ink">
                                {formatCount(value)}
                            </p>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

function LinkCard({ table, index }) {
    return (
        <Link
            href={route(table.route)}
            className="group ui-panel ui-panel-interactive block animate-fade-up p-5"
            style={{ animationDelay: `${120 + index * 40}ms` }}
        >
            <div className="flex items-start gap-4">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-muted text-brand">
                    <Icon name={TABLE_ICONS[table.key]} />
                </div>
                <div className="min-w-0 flex-1">
                    <h3 className="font-display text-base font-semibold text-ink">{table.label}</h3>
                    <p className="mt-1 text-sm leading-relaxed text-ink-muted">
                        {table.description}
                    </p>
                </div>
                <div className="flex shrink-0 flex-col items-end gap-3">
                    <span className="rounded-md bg-brand-muted px-2.5 py-1 text-sm font-semibold leading-none text-brand-deep">
                        {formatCount(table.count)}
                    </span>
                    <span className="flex h-8 w-8 items-center justify-center rounded-full border border-brand-line text-brand transition-colors duration-220 ease-matex group-hover:border-brand group-hover:bg-brand group-hover:text-white">
                        <Icon name="arrowRight" className="h-4 w-4" />
                    </span>
                </div>
            </div>
        </Link>
    );
}

export default function Dashboard({ stats, tables }) {
    const heroValue = stats[HERO_KEY];
    const stripEntries = Object.entries(stats).filter(([key]) => key !== HERO_KEY);

    return (
        <AdminLayout
            title="Overview"
            description="Kelola master data base proses Matex: item master QAD, suppliers, dan kedisiplinan RM."
        >
            <Head title="Master Data" />

            <div className="space-y-6">
                <div className="grid gap-4 lg:grid-cols-6">
                    {heroValue !== undefined && <HeroStat value={heroValue} />}
                    <StatStrip entries={stripEntries} />
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {tables.map((table, index) => (
                        <LinkCard key={table.key} table={table} index={index} />
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
