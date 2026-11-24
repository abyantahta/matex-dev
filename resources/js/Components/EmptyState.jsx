export default function EmptyState({ title, description }) {
    return (
        <div className="animate-fade-up rounded-panel border-2 border-dashed border-line-strong bg-surface px-6 py-14 text-center">
            <h3 className="font-display text-[0.9375rem] font-semibold text-ink">{title}</h3>
            {description && (
                <p className="mx-auto mt-2 max-w-md text-sm text-ink-muted">{description}</p>
            )}
        </div>
    );
}
