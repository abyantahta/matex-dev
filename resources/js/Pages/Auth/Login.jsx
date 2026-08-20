import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            {status && (
                <div className="mb-4 rounded-md border border-brand-line bg-brand-muted px-3 py-2 text-sm font-medium text-brand-deep">
                    {status}
                </div>
            )}

            <div className="mb-5 rounded-panel border border-brand-line bg-brand-muted/70 p-3.5 text-xs text-brand-deep">
                <p className="font-display text-sm font-semibold">Demo accounts</p>
                <p className="mt-0.5 text-ink-muted">password: password</p>
                <ul className="mt-2 space-y-1 text-ink-soft">
                    <li>dita@matex.test — Purchasing</li>
                    <li>rm@matex.test — Supplier RM</li>
                    <li>ohp@matex.test — Supplier OHP</li>
                    <li>ppic@matex.test — PPIC</li>
                </ul>
            </div>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1.5 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Password" />
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1.5 block w-full"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="block">
                    <label className="flex items-center gap-2">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                        />
                        <span className="text-sm text-ink-muted">Remember me</span>
                    </label>
                </div>

                <div className="flex items-center justify-between gap-3 pt-1">
                    {canResetPassword ? (
                        <Link
                            href={route('password.request')}
                            className="text-sm text-ink-muted underline decoration-line underline-offset-4 transition hover:text-brand"
                        >
                            Forgot password?
                        </Link>
                    ) : (
                        <span />
                    )}

                    <PrimaryButton disabled={processing}>Log in</PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
