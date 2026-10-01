/**
 * Semua upload file dikirim sebagai base64, bukan multipart biner: Cloudflare
 * WAF di depan server memblokir body yang berisi PDF/gambar mentah (HTTP 403
 * sebelum request sampai ke aplikasi). Server (trait AcceptsBase64Uploads)
 * mendekodenya kembali menjadi file biasa pada field `<name>`.
 */

/** Baca File menjadi string base64 murni (tanpa prefix data-URL). */
export function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            const result = String(reader.result || '');
            resolve(result.slice(result.indexOf(',') + 1));
        };
        reader.onerror = () => reject(reader.error || new Error('Gagal membaca file.'));
        reader.readAsDataURL(file);
    });
}

/**
 * Bentuk payload untuk field upload `name`:
 * `{ [name]_base64: '...', [name]_name: 'asli.pdf' }`, atau null jika kosong.
 */
export async function encodeUploadField(name, file) {
    if (!file) {
        return { [`${name}_base64`]: null, [`${name}_name`]: null };
    }

    return {
        [`${name}_base64`]: await fileToBase64(file),
        [`${name}_name`]: file.name,
    };
}

export function formatFileSize(bytes) {
    if (!Number.isFinite(bytes)) return '';
    if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}
