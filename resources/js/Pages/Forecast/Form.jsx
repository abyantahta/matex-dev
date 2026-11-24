import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { encodeUploadField, formatFileSize } from '@/utils/upload';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const FILE_MAX_MB = 10;

export default function Form({ forecast, defaultPeriod, defaultSupplierId, suppliersRm }) {
    const editing = Boolean(forecast);
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm({
        supplier_rm_id: String(forecast?.supplier_rm_id || defaultSupplierId || ''),
        period_month: forecast?.period_month
            ? String(forecast.period_month).slice(0, 7)
            : defaultPeriod || '',
        notes: forecast?.notes || '',
        file_base64: null,
        file_name: null,
    });

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

    const canSubmit = Boolean(data.file_base64) || Boolean(forecast?.file_path);

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <p className="ui-eyebrow">Planning</p>
                    <h2 className="ui-section-title mt-1">
                        {editing ? 'Perbarui Forecast' : 'Upload Forecast'}
                    </h2>
                    <p className="mt-1 text-sm text-ink-muted">
                        Upload file PDF atau Excel saja. Tidak perlu menambah item number.
                    </p>
                </div>
            }
        >
            <Head title={editing ? 'Perbarui Forecast' : 'Upload Forecast'} />

            <div className="ui-page">
                <form onSubmit={submit} className="space-y-4">
                    <section className="ui-panel p-5 sm:p-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <InputLabel value="Supplier Raw Material" />
                                <select
                                    className="mt-1 w-full rounded-xl border-line"
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
                                    className="mt-1 w-full rounded-xl border-line"
                                    rows={2}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                                <InputError message={errors.notes} className="mt-1" />
                            </div>
                            <div className="md:col-span-2">
                                <InputLabel value="File forecast (PDF / Excel)" />
                                <input
                                    type="file"
                                    className="mt-1 block w-full text-sm"
                                    accept=".pdf,.xlsx,.xls,.csv"
                                    onChange={(e) => pickFile(e.target.files?.[0] || null)}
                                />
                                <p className="mt-1 text-xs text-ink-muted">
                                    PDF atau Excel — maks. {FILE_MAX_MB} MB
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

                    <div className="flex justify-end gap-3">
                        <Link
                            href={route('forecasts.index')}
                            className="rounded-xl border border-line px-4 py-2 text-sm"
                        >
                            Batal
                        </Link>
                        <PrimaryButton disabled={processing || fileEncoding || !canSubmit}>
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
