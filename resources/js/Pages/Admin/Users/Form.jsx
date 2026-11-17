import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableSelect from '@/Components/SearchableSelect';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const autofillGuard = {
    'data-lpignore': 'true',
    'data-1p-ignore': 'true',
    'data-bwignore': 'true',
    'data-form-type': 'other',
};

const SUPPLIER_ROLE_CATEGORY = {
    supplier_rm: 'raw_mat',
    supplier_ohp: 'ohp',
};

export default function Form({ user, prefill, qadSuppliers, roles }) {
    const editing = Boolean(user);
    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name || '',
        email: user?.email || '',
        phone: user?.phone || '',
        password: '',
        password_confirmation: '',
        role: user?.role || prefill?.role || roles[0]?.value || 'purchasing',
        supplier_code: user?.company?.code || prefill?.supplier_code || '',
        broadcast_emails: user?.broadcast_emails || [],
    });
    const [broadcastInput, setBroadcastInput] = useState('');
    const [unlockAutofill, setUnlockAutofill] = useState(false);

    const addBroadcastEmail = () => {
        const emails = broadcastInput
            .split(/[,;\s]+/)
            .map((email) => email.trim().toLowerCase())
            .filter(Boolean);
        if (!emails.length) {
            return;
        }
        setData('broadcast_emails', [...new Set([...(data.broadcast_emails || []), ...emails])]);
        setBroadcastInput('');
    };

    const supplierCategory = SUPPLIER_ROLE_CATEGORY[data.role];
    const supplierOptions = supplierCategory
        ? qadSuppliers.filter((s) => s.category === supplierCategory)
        : [];

    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            put(route('admin.users.update', user.id));
        } else {
            post(route('admin.users.store'));
        }
    };

    return (
        <AdminLayout
            title={editing ? `Edit ${user.name}` : 'Tambah User'}
            description="Atur kredensial login, role, dan supplier (untuk role Supplier RM/OHP)."
        >
            <Head title={editing ? 'Edit User' : 'Tambah User'} />

            <form
                onSubmit={submit}
                autoComplete="off"
                className="relative max-w-2xl space-y-4 ui-panel p-5 sm:p-6"
            >
                <div aria-hidden="true" className="sr-only">
                    <input type="text" name="username" autoComplete="username" tabIndex={-1} />
                    <input type="password" name="current-password" autoComplete="current-password" tabIndex={-1} />
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Name" />
                        <TextInput
                            className="mt-1 w-full"
                            autoComplete="off"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Email" />
                        <TextInput
                            type="email"
                            name="user_email"
                            autoComplete={editing ? 'email' : 'off'}
                            readOnly={!editing && !unlockAutofill}
                            onFocus={() => setUnlockAutofill(true)}
                            className="mt-1 w-full read-only:bg-surface"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            {...autofillGuard}
                        />
                        <InputError message={errors.email} className="mt-1" />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel
                            value={
                                data.role === 'supplier_rm'
                                    ? 'No. WhatsApp (wajib untuk Supplier RM)'
                                    : 'No. WhatsApp'
                            }
                        />
                        <TextInput
                            type="tel"
                            className="mt-1 w-full"
                            placeholder="Contoh: 081234567890"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                        <InputError message={errors.phone} className="mt-1" />
                        {data.role === 'supplier_rm' && (
                            <p className="mt-1 text-xs text-ink-muted">
                                Dipakai untuk kirim notifikasi WhatsApp saat PO
                                dikirim/dikonfirmasi.
                            </p>
                        )}
                    </div>
                    <div />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel
                            value={editing ? 'Password baru (opsional)' : 'Password'}
                        />
                        <TextInput
                            type="password"
                            name="new_password"
                            autoComplete="new-password"
                            readOnly={!unlockAutofill}
                            onFocus={() => setUnlockAutofill(true)}
                            className="mt-1 w-full read-only:bg-surface"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            {...autofillGuard}
                        />
                        <InputError message={errors.password} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Konfirmasi Password" />
                        <TextInput
                            type="password"
                            name="new_password_confirmation"
                            autoComplete="new-password"
                            readOnly={!unlockAutofill}
                            onFocus={() => setUnlockAutofill(true)}
                            className="mt-1 w-full read-only:bg-surface"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            {...autofillGuard}
                        />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Role" />
                        <select
                            className="mt-1 w-full rounded-md border-line"
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                        >
                            {roles.map((r) => (
                                <option key={r.value} value={r.value}>
                                    {r.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.role} className="mt-1" />
                    </div>
                    {supplierCategory && (
                        <div>
                            <InputLabel value="Supplier" />
                            <SearchableSelect
                                options={supplierOptions}
                                value={data.supplier_code}
                                placeholder="Ketik kode / nama supplier..."
                                getOptionValue={(s) => s.qad_code}
                                getOptionLabel={(s) =>
                                    `${s.qad_code} — ${s.name}${s.city ? ` (${s.city})` : ''}`
                                }
                                onChange={(val) => setData('supplier_code', val)}
                            />
                            <InputError message={errors.supplier_code} className="mt-1" />
                            {supplierOptions.length === 0 && (
                                <p className="mt-1 text-xs text-amber-700">
                                    Belum ada supplier bertanda kategori ini. Tandai dulu di
                                    halaman{' '}
                                    <Link
                                        href={route('admin.qad-suppliers.index')}
                                        className="underline"
                                    >
                                        Suppliers
                                    </Link>
                                    .
                                </p>
                            )}
                        </div>
                    )}
                </div>

                <div>
                    <InputLabel value="Email broadcast (opsional)" />
                    <p className="mt-0.5 text-xs text-ink-muted">
                        Tembusan notifikasi. Login tetap memakai email utama.
                    </p>
                    <div className="mt-2 flex gap-2">
                        <TextInput
                            type="email"
                            autoComplete="off"
                            className="w-full"
                            placeholder="ketik email, koma, lalu Tambah"
                            value={broadcastInput}
                            onChange={(e) => setBroadcastInput(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' || e.key === ',') {
                                    e.preventDefault();
                                    addBroadcastEmail();
                                }
                            }}
                        />
                        <button
                            type="button"
                            className="rounded-md border border-line px-3 py-2 text-sm"
                            onClick={addBroadcastEmail}
                        >
                            Tambah
                        </button>
                    </div>
                    <div className="mt-2 flex flex-wrap gap-2">
                        {(data.broadcast_emails || []).map((email) => (
                            <span
                                key={email}
                                className="inline-flex items-center gap-1 rounded-full bg-brand-muted px-2 py-0.5 text-xs text-brand-deep"
                            >
                                {email}
                                <button
                                    type="button"
                                    onClick={() =>
                                        setData(
                                            'broadcast_emails',
                                            data.broadcast_emails.filter((item) => item !== email),
                                        )
                                    }
                                >
                                    ×
                                </button>
                            </span>
                        ))}
                    </div>
                    <InputError message={errors.broadcast_emails} className="mt-1" />
                </div>

                <div className="flex justify-end gap-3 pt-2">
                    <Link
                        href={route('admin.users.index')}
                        className="rounded-md border border-line px-4 py-2 text-sm"
                    >
                        Batal
                    </Link>
                    <PrimaryButton
                        disabled={processing}
                        className="bg-brand hover:bg-brand-deep"
                    >
                        {editing ? 'Simpan' : 'Tambah'}
                    </PrimaryButton>
                </div>
            </form>
        </AdminLayout>
    );
}
