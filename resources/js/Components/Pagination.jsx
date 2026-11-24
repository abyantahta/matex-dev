import { Link } from '@inertiajs/react';

export default function Pagination({ paginator }) {
    const links = Array.isArray(paginator?.links)
        ? paginator.links
        : paginator?.meta?.links || [];

    if (!links.length || links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-end gap-1 border-t border-line px-4 py-3">
            {links.map((link, index) => {
                const label = String(link.label)
                    .replace('&laquo;', '«')
                    .replace('&raquo;', '»');

                if (!link.url) {
                    return (
                        <span
                            key={`${label}-${index}`}
                            className="rounded-xl px-2.5 py-1 text-sm text-ink-faint"
                            dangerouslySetInnerHTML={{ __html: label }}
                        />
                    );
                }

                return (
                    <Link
                        key={`${label}-${index}`}
                        href={link.url}
                        preserveState
                        preserveScroll
                        className={`min-w-[2rem] rounded-xl px-2.5 py-1 text-center text-sm transition duration-220 ease-matex ${
                            link.active
                                ? 'bg-brand-muted font-semibold text-brand-deep ring-1 ring-inset ring-brand-line'
                                : 'text-ink-soft hover:bg-brand-muted hover:text-brand-deep'
                        }`}
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                );
            })}
        </div>
    );
}
