export default function CalendarTable({ calendar }) {
    const days = Array.from({ length: calendar.days || 0 }, (_, i) => i + 1);

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full border-collapse text-sm">
                <thead>
                    <tr className="ui-table-head">
                        <th className="sticky left-0 z-10 bg-canvas-soft px-3 py-2 text-left">
                            Supplier
                        </th>
                        {days.map((day) => (
                            <th key={day} className="min-w-24 px-1 py-2 text-center">
                                {day}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-line">
                    {(calendar.rows || []).map((row) => (
                        <tr key={row.supplier.id}>
                            <td className="sticky left-0 z-10 bg-surface px-3 py-2 font-medium text-ink">
                                <div>{row.supplier.name}</div>
                                <div className="text-xs text-ink-muted">{row.supplier.code}</div>
                            </td>
                            {days.map((day) => (
                                <td key={day} className="align-top px-1 py-1 text-center">
                                    <div className="flex flex-col items-center gap-1">
                                        {(row.cells?.[day] || []).map((chip, index) => (
                                            <div
                                                key={`${chip.item_number}-${index}`}
                                                className={
                                                    'w-full rounded px-1 py-1 text-[11px] leading-tight ' +
                                                    (chip.shipped
                                                        ? 'bg-emerald-100 text-emerald-900'
                                                        : 'bg-amber-100 text-amber-900')
                                                }
                                            >
                                                <div className="font-semibold">{chip.item_number}</div>
                                                <div>
                                                    {chip.qty} kg
                                                    {!chip.shipped ? ' · plan' : ''}
                                                </div>
                                                {chip.show_plan_note && chip.plan_date && (
                                                    <div className="text-[10px] opacity-80">
                                                        plan {Number(String(chip.plan_date).slice(8, 10))}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
            {!(calendar.rows || []).length && (
                <p className="px-4 py-8 text-center text-sm text-ink-muted">
                    Belum ada plan pengiriman di bulan ini.
                </p>
            )}
        </div>
    );
}
