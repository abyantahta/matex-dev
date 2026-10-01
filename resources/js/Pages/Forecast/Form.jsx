import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableSelect from '@/Components/SearchableSelect';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatQty, isQtyInput, toNum } from '@/utils/qty';
import { encodeUploadField, formatFileSize } from '@/utils/upload';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const FILE_MAX_MB = 10;

function emptyLine() {
    return { item_id: '', qty: '' };
}

export default function Form({
    forecast,
    defaultPeriod,
    defaultSupplierId,
    suppliersRm,
    items,
}) {
    const editing = Boolean(forecast);
    const { data, setData, post, processing, errors, transform, setError, clearErrors } = useForm({
        supplier_rm_id: String(forecast?.supplier_rm_id || defaultSupplierId || ''),
        period_month: forecast?.period_month
            ? String(forecast.period_month).slice(0, 7)
            : defaultPeriod || '',
        notes: forecast?.notes || '',
        // File dikirim sebagai base64 (lihat utils/upload.js) agar tidak diblokir WAF.
        file_base64: null,
        file_name: null,
        items:
            forecast?.items?.length > 0
                ? forecast.items.map((line) => ({
                      item_id: String(line.item_id || ''),
                      qty: String(line.qty ?? ''),
                  }))
                : [emptyLine()],
    });

    transform((form) => ({
        ...form,
        items: (form.items || [])
            .filter((line) => line.item_id && toNum(line.qty) > 0)
            .map((line) => ({
                item_id: line.item_id,
                qty: toNum(line.qty),
            })),
    }));

    const updateLine = (index, key, value) => {
        const next = [...data.items];
        next[index] = { ...next[index], [key]: value };
        setData('items', next);
    };

    const [fileEncoding, setFileEncoding] = useState(false);
    const pickFile = async (file) => {
        clearErrors('file');
        if (!file) {
            setData((prev) => ({ ...prev, file_base64: null, file_name: null }));
            return;
        }
        if (file.size > FILE_MAX_MB * 1024 * 1024) {
            setData((prev) => ({ ...prev, file_base64: null, file_name: null }));
            setError(
                'file',
                `Ukuran file ${formatFileSize(file.size)} melebihi batas ${FILE_MAX_MB} MB.`,
            );
            return;
        }
        setFileEncoding(true);
        try {
            const encoded = await encodeUploadField('file', file);
            setData((prev) => ({ ...prev, ...encoded }));
        } catch (err) {
            setError('file', err?.message || 'Gagal membaca file.');
        } finally {
            setFileEncoding(false);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            post(route('forecasts.update', forecast.id));
        } else {
            post(route('forecasts.store'));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <p className="ui-eyebrow">Planning</p>
                    <h2 className="ui-section-title mt-1">
                        {editing ? 'Perbarui Forecast' : 'Upload Forecast'}
                    </h2>
                    <p className="mt-1 text-sm text-ink-muted">
                        Forecast per supplier RM, berlaku untuk satu bulan. Upload ulang pada
                        supplier & periode yang sama akan menggantikan data sebelumnya.
                    </p>
                </div>
            }
        >
            <Head title={editing ? 'Perbarui Forecast' : 'Upload Forecast'} />

            <div className="ui-page">
                <form onSubmit={submit} className="space-y-4">
                    <section className="ui-panel p-5 sm:p-6">
                        <h3 className="mb-4 font-display font-semibold text-ink">Header</h3>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <InputLabel value="Supplier Raw Material" />
                                <select
                                    className="mt-1 w-full rounded-md border-line"
                                    value={data.supplier_rm_id}
                                    onChange={(e) => setData('supplier_rm_id', e.target.value)}
                                >
                                    <option value="">Pilih supplier</option>
                                    {suppliersRm.map((supplier) => (
                                        <option key={supplier.id} value={supplier.id}>
                                            {supplier.code} — {supplier.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.supplier_rm_id} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Periode (bulan)" />
                                <TextInput
                                    type="month"
                                    className="mt-1 w-full"
                                    value={data.period_month}
                                    onChange={(e) => setData('period_month', e.target.value)}
                                />
                                <InputError message={errors.period_month} className="mt-1" />
                            </div>
                            <div className="md:col-span-2">
                                <InputLabel value="Catatan" />
                                <textarea
                                    className="mt-1 w-full rounded-md border-line"
                                    rows={2}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                                <InputError message={errors.notes} className="mt-1" />
                            </div>
                            <div className="md:col-span-2">
                                <InputLabel value="File forecast (opsional)" />
                                <input
                                    type="file"
                                    className="mt-1 block w-full text-sm"
                                    accept=".pdf,.xlsx,.xls,.csv,.jpg,.jpeg,.png"
                                    onChange={(e) => pickFile(e.target.files?.[0] || null)}
                                />
                                <p className="mt-1 text-xs text-ink-muted">
                                    PDF, Excel, CSV, JPG, atau PNG — maks. {FILE_MAX_MB} MB
                                    {fileEncoding && ' · Membaca file…'}
                                </p>
                                {forecast?.original_filename && !data.file_base64 && (
                                    <p className="mt-1 text-xs text-ink-muted">
                                        File saat ini: {forecast.original_filename}
                                    </p>
                                )}
                                <InputError message={errors.file} className="mt-1" />
                            </div>
                        </div>
                    </section>

                    <section className="ui-panel p-5 sm:p-6">
                        <h3 className="font-display font-semibold text-ink">Item Forecast</h3>
                        <p className="mb-4 mt-1 text-sm text-ink-muted">
                            Isi item number dan qty (kg, bilangan bulat). Boleh dikosongkan jika
                            hanya mengunggah file.
                        </p>
                        <div className="space-y-3">
                            {data.items.map((line, index) => (
                                <div
                                    key={index}
                                    className="grid gap-3 rounded-lg border border-line bg-canvas-soft p-4 md:grid-cols-[1fr_160px_auto]"
                                >
                                    <div>
                                        <InputLabel value="Item Number" />
                                        <SearchableSelect
                                            options={items}
                                            value={line.item_id}
                                            placeholder="Ketik item number..."
                                            getOptionValue={(item) => String(item.id)}
                                            getOptionLabel={(item) =>
                                                `${item.item_number} — ${item.description}`
                                            }
                                            onChange={(val) => updateLine(index, 'item_id', val)}
                                        />
                                        <InputError
                                            message={errors[`items.${index}.item_id`]}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel value="Qty (kg)" />
                                        <TextInput
                                            type="text"
                                            inputMode="numeric"
                                            className="mt-1 w-full"
                                            value={line.qty}
                                            onChange={(e) => {
                                                if (isQtyInput(e.target.value)) {
                                                    updateLine(index, 'qty', e.target.value);
                                                }
                                            }}
                                        />
                                        <InputError
                                            message={errors[`items.${index}.qty`]}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div className="flex items-end">
                                        {data.items.length > 1 && (
                                            <button
                                                type="button"
                                                className="pb-2 text-sm text-rose-600"
                                                onClick={() =>
                                                    setData(
                                                        'items',
                                                        data.items.filter((_, i) => i !== index),
                                                    )
                                                }
                                            >
                                                Hapus
                                            </button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                        <button
                            type="button"
                            className="pressable mt-4 text-sm font-semibold text-brand hover:text-brand-deep"
                            onClick={() => setData('items', [...data.items, emptyLine()])}
                        >
                            + Tambah item
                        </button>
                        <InputError message={errors.items} className="mt-2" />
                        <p className="mt-3 text-xs text-ink-muted">
                            Total qty:{' '}
                            <span className="font-semibold text-ink">
                                {formatQty(
                                    data.items.reduce((sum, line) => sum + toNum(line.qty), 0),
                                )}{' '}
                                kg
                            </span>
                        </p>
                    </section>

                    <div className="flex justify-end gap-3">
                        <Link
                            href={route('forecasts.index')}
                            className="rounded-md border border-line px-4 py-2 text-sm"
                        >
                            Batal
                        </Link>
                        <PrimaryButton disabled={processing || fileEncoding}>
                            {processing
                                ? 'Menyimpan…'
                                : editing
                                  ? 'Simpan Perubahan'
                                  : 'Upload Forecast'}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
