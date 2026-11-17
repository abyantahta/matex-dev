import InputError from '@/Components/InputError';
import Pagination from '@/Components/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { formatPrice, isPriceInput } from '@/utils/money';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

function PriceRow({ item }) {
    const { data, setData, patch, processing, errors } = useForm({
        price: formatPrice(item.price),
    });

    return (
        <tr>
            <td className="px-4 py-2 font-medium text-ink">{item.qad_code}</td>
            <td className="px-4 py-2 text-ink-soft">{item.description}</td>
            <td className="px-4 py-2">
                <div className="flex items-center gap-2">
                    <TextInput
                        className="w-36"
                        value={data.price}
                        onChange={(e) => {
                            if (isPriceInput(e.target.value)) {
                                setData('price', e.target.value.replace(',', '.'));
                            }
                        }}
                    />
                    <PrimaryButton
                        type="button"
                        className="bg-brand"
                        disabled={processing}
                        onClick={() => patch(route('admin.prices.update', item.qad_code))}
                    >
                        Simpan
                    </PrimaryButton>
                </div>
                <InputError message={errors.price} className="mt-1" />
            </td>
        </tr>
    );
}

export default function Index({ items, filters }) {
    const [q, setQ] = useState(filters.q || '');
    const importForm = useForm({ file: null });

    return (
        <AdminLayout
            title="Harga"
            description="Harga default per kg. Dipakai sebagai nilai awal saat buat PO, masih bisa diubah di transaksi."
            actions={
                <div className="flex flex-wrap gap-2">
                    <a
                        href={route('admin.prices.export')}
                        className="rounded-md border border-line px-3 py-2 text-sm font-medium"
                    >
                        Export
                    </a>
                </div>
            }
        >
            <Head title="Master Harga" />

            <div className="space-y-4">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <form
                        className="flex gap-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            router.get(route('admin.prices.index'), { q: q || undefined });
                        }}
                    >
                        <TextInput
                            placeholder="Cari item number / deskripsi"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                        />
                        <PrimaryButton className="bg-brand">Cari</PrimaryButton>
                    </form>

                    <form
                        className="flex items-center gap-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            importForm.post(route('admin.prices.import'), { forceFormData: true });
                        }}
                    >
                        <input
                            type="file"
                            accept=".xls,.xlsx,.csv,.xml"
                            onChange={(e) => importForm.setData('file', e.target.files?.[0] || null)}
                        />
                        <PrimaryButton className="bg-ink" disabled={importForm.processing || !importForm.data.file}>
                            Import
                        </PrimaryButton>
                    </form>
                </div>
                <InputError message={importForm.errors.file} />

                <div className="ui-panel overflow-hidden">
                    <table className="min-w-full divide-y divide-line text-sm">
                        <thead className="ui-table-head">
                            <tr>
                                <th className="px-4 py-3 text-left">Item number</th>
                                <th className="px-4 py-3 text-left">Description</th>
                                <th className="px-4 py-3 text-left">Harga / kg (Rp)</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-line">
                            {items.data.map((item) => (
                                <PriceRow key={item.id} item={item} />
                            ))}
                        </tbody>
                    </table>
                </div>
                <Pagination paginator={items} />
            </div>
        </AdminLayout>
    );
}
