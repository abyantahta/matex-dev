import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ user, companies, roles }) {
    const editing = Boolean(user);
    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name || '',
        email: user?.email || '',
        password: '',
        password_confirmation: '',
        role: user?.role || 'purchasing',
        company_id: user?.company_id || '',
    });

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
            description="Atur kredensial login, role, dan company."
        >
            <Head title={editing ? 'Edit User' : 'Tambah User'} />

            <form
                onSubmit={submit}
                className="max-w-2xl space-y-4 ui-panel p-5 sm:p-6"
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Name" />
                        <TextInput
                            className="mt-1 w-full"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Email" />
                        <TextInput
                            type="email"
                            className="mt-1 w-full"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        <InputError message={errors.email} className="mt-1" />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel
                            value={editing ? 'Password baru (opsional)' : 'Password'}
                        />
                        <TextInput
                            type="password"
                            className="mt-1 w-full"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <InputError message={errors.password} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Konfirmasi Password" />
                        <TextInput
                            type="password"
                            className="mt-1 w-full"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
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
                    <div>
                        <InputLabel value="Company" />
                        <select
                            className="mt-1 w-full rounded-md border-line"
                            value={data.company_id}
                            onChange={(e) => setData('company_id', e.target.value)}
                        >
                            <option value="">— Tidak ada —</option>
                            {companies.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.code} — {c.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.company_id} className="mt-1" />
                    </div>
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
