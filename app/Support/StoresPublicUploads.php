<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

trait StoresPublicUploads
{
    /**
     * Tulis file ke disk public, lalu pastikan path-nya benar-benar ada.
     * store() bisa gagal diam-diam (folder milik root, throw=false) dan
     * mengembalikan "0" — jangan simpan path palsu itu ke database.
     */
    protected function storePublicUpload(UploadedFile $file, string $directory): string
    {
        $disk = Storage::disk('public');
        $path = $file->store($directory, 'public');

        if (! is_string($path) || $path === '' || $path === '0' || ! $disk->exists($path)) {
            throw new RuntimeException("File gagal diunggah ke storage ({$directory}).");
        }

        return $path;
    }
}
