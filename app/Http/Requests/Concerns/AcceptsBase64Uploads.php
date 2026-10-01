<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Http\UploadedFile;

/**
 * Cloudflare WAF memblokir body multipart yang berisi file biner mentah
 * (PDF/gambar) dengan HTTP 403 sebelum request sampai ke nginx. Browser
 * karena itu mengirim isi file sebagai teks base64 (`<field>_base64` +
 * `<field>_name`); trait ini mendekodenya kembali menjadi UploadedFile pada
 * `<field>` sehingga rules (`file`, `mimes`, `max`), controller, action, dan
 * test yang memakai upload multipart biasa tetap berlaku tanpa perubahan.
 */
trait AcceptsBase64Uploads
{
    /**
     * @param  array<string, int>  $fields  nama field => batas ukuran (KB)
     */
    protected function decodeBase64Uploads(array $fields): void
    {
        foreach ($fields as $field => $maxKb) {
            if ($this->hasFile($field) || ! $this->filled("{$field}_base64")) {
                continue;
            }

            $file = $this->base64ToUploadedFile(
                (string) $this->input("{$field}_base64"),
                (string) ($this->input("{$field}_name") ?: $field),
                $maxKb,
            );

            if ($file) {
                $this->files->set($field, $file);
                // hasFile() di atas sudah meng-cache daftar file; reset agar file baru terlihat.
                $this->convertedFiles = null;
            }

            // Sumber input bisa JSON (Inertia tanpa FormData) atau form biasa.
            $source = $this->getInputSource();
            $source->remove("{$field}_base64");
            $source->remove("{$field}_name");
        }
    }

    private function base64ToUploadedFile(string $encoded, string $name, int $maxKb): ?UploadedFile
    {
        // Terima juga bentuk data-URL ("data:application/pdf;base64,....").
        if (str_contains($encoded, ',')) {
            $encoded = substr($encoded, strrpos($encoded, ',') + 1);
        }

        // Tolak lebih awal payload yang jelas melebihi batas (base64 ≈ 4/3 ukuran asli);
        // field dibiarkan kosong sehingga rule `required`/`file` yang memberi pesan.
        if (strlen($encoded) > $maxKb * 1024 * 4 / 3 + 1024) {
            return null;
        }

        $binary = base64_decode($encoded, true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'b64upload_');
        if ($path === false || file_put_contents($path, $binary) === false) {
            return null;
        }

        // Bersihkan file sementara bila validasi gagal; jika sukses, store() memindahkannya.
        app()->terminating(function () use ($path) {
            if (is_file($path)) {
                @unlink($path);
            }
        });

        // Mime dibiarkan null agar rule `mimes` menebak dari isi file (finfo), bukan dari klien.
        return new UploadedFile($path, basename($name), null, null, true);
    }
}
