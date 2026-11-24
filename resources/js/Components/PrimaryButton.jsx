export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    // When a page passes its own background (e.g. a green "approve" button),
    // let it win instead of fighting the default ink fill.
    const hasCustomBg = /(^|\s)bg-/.test(className);
    const hasCustomText = /(^|\s)!?text-(?!xs|sm|base|lg|xl|\[)/.test(className);
    const tone = hasCustomBg
        ? `${hasCustomText ? '' : 'text-white'} hover:brightness-110 active:brightness-95`
        : 'bg-brand text-white hover:bg-brand-deep active:bg-brand-deep';

    return (
        <button
            {...props}
            className={
                `pressable inline-flex items-center justify-center gap-2 rounded-xl border border-transparent px-4 py-2.5 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:translate-y-0 ${tone} ${className}`
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
