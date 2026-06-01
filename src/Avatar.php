<?php

/**
 * Upload foto profil (avatar) untuk user customer & admin/staff.
 *
 * Disimpan ke /uploads/avatars/ dan return path relatif untuk users.avatar_url.
 * Hanya menerima gambar raster (PNG/JPG/GIF/WEBP) — SVG sengaja ditolak untuk
 * menghindari risiko konten aktif. MIME dideteksi dari isi file, bukan nama.
 */
class Avatar
{
    /**
     * @param array<string,mixed> $file Entri dari $_FILES.
     * @return array{ok:bool,url:string,error:string}
     */
    public static function handleUpload(array $file, string $prefix, string $subdir = 'avatars'): array
    {
        $fail = static fn(string $msg): array => ['ok' => false, 'url' => '', 'error' => $msg];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $fail('Upload foto gagal (kode: ' . (int)($file['error'] ?? -1) . ')');
        }

        $maxBytes = 2 * 1024 * 1024; // 2 MB
        if ((int)($file['size'] ?? 0) > $maxBytes) {
            return $fail('Ukuran foto maksimal 2 MB');
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || (!is_uploaded_file($tmp) && !is_file($tmp))) {
            return $fail('File foto tidak valid');
        }

        $allowed = [
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = (string)finfo_file($finfo, $tmp);
            finfo_close($finfo);
        }
        if (!isset($allowed[$mime])) {
            return $fail('Format foto harus PNG, JPG, GIF, atau WEBP');
        }

        $subdir = preg_replace('/[^a-z0-9_-]/', '', $subdir) ?: 'avatars';
        $dir = BASE_PATH . '/uploads/' . $subdir;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $fail('Gagal membuat folder upload');
        }

        $filename = $prefix . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
        $dest = $dir . '/' . $filename;

        $moved = is_uploaded_file($tmp) ? move_uploaded_file($tmp, $dest) : rename($tmp, $dest);
        if (!$moved) {
            return $fail('Gagal menyimpan foto');
        }

        return ['ok' => true, 'url' => '/uploads/' . $subdir . '/' . $filename, 'error' => ''];
    }
}
