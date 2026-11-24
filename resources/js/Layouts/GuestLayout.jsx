import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen bg-canvas">
            {/* Photo panel */}
            <div className="relative hidden w-[46%] max-w-[720px] p-4 lg:block">
                <div className="relative flex h-full flex-col justify-between overflow-hidden rounded-[2rem] p-10 xl:p-12">
                    <img
                        src="/images/login-hero-coil.jpg"
                        alt="Gulungan coil baja di gudang"
                        className="absolute inset-0 h-full w-full object-cover object-[38%_50%] brightness-[0.85] contrast-[1.2] grayscale"
                    />
                    {/* pastel duotone: "screen" lifts the black shadows into lavender/pink, highlights stay white */}
                    <div className="absolute inset-0 bg-gradient-to-br from-[#9C8FEA] via-[#C4A9EA] to-[#E9AEC6] mix-blend-screen" />
                    {/* soft white fade at the bottom keeps the tagline readable */}
                    <div className="absolute inset-0 bg-gradient-to-t from-white/80 via-white/0 to-white/20" />

                    <Link href="/" className="relative flex items-center gap-3">
                        <ApplicationLogo className="h-11 w-11 rounded-[14px] ring-4 ring-white/60" />
                        <span className="font-display text-xl font-extrabold tracking-[0.06em] text-ink">
                            MATEX
                        </span>
                    </Link>

                    <div className="relative">
                        <p className="max-w-sm font-display text-2xl font-bold leading-snug text-ink xl:text-3xl">
                            One-Stop Portal for Material Traceability
                        </p>
                        <p className="mt-3 text-xs text-ink-muted">&copy; {new Date().getFullYear()} MATEX</p>
                    </div>
                </div>
            </div>

            {/* Form side */}
            <div className="relative flex flex-1 flex-col items-center justify-center overflow-hidden px-5 py-10 sm:px-10">
                <div className="pointer-events-none absolute -right-16 -top-16 h-72 w-72 rounded-full bg-violet-100 blur-3xl" />
                <div className="pointer-events-none absolute -bottom-20 -left-10 h-64 w-64 rounded-full bg-amber-50 blur-3xl" />

                <div className="relative w-full max-w-[25rem] animate-fade-up">
                    <Link href="/" className="mb-8 flex items-center gap-3">
                        <ApplicationLogo className="h-11 w-11 lg:hidden" />
                        <div>
                            <span className="font-display text-2xl font-extrabold tracking-tight text-ink">
                                <span className="lg:hidden">MATEX</span>
                                <span className="hidden lg:inline">Masuk ke MATEX</span>
                            </span>
                            <p className="mt-1 text-sm text-ink-muted">Material Exchange Portal</p>
                        </div>
                    </Link>

                    <div className="rounded-3xl border border-line bg-surface p-6 shadow-panel sm:p-7">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
