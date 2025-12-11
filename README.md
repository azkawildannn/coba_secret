# Simple News Viewer (PHP)

Project kecil: menampilkan berita nyata dari sebuah News API. Aplikasi ini tidak menyimpan API key di repo — kunci dibaca dari environment.

Setup singkat
- Pastikan PHP terinstall (PHP 7.4+ direkomendasikan).
- Tambahkan API key Anda sebagai secret atau environment variable `NEWS_API_KEY`.

Cara menjalankan lokal (PowerShell):
```
# set environment variable untuk sesi terminal
$env:NEWS_API_KEY = "<your_api_key_here>";
# jalankan built-in server PHP
php -S localhost:8000
```

Lalu buka `http://localhost:8000` di browser.

Menambahkan GitHub secret (web UI)
- Buka repo di GitHub → `Settings` → `Secrets and variables` → `Actions` → `New repository secret`.
- Nama: `NEWS_API_KEY` — Isi: (tempel API key Anda) — Simpan.

Menambahkan GitHub secret (gh CLI)
- Pastikan `gh` sudah ter-install dan ter-autentikasi; jalankan **dalam root repo**:
```
gh secret set NEWS_API_KEY --body "<your_api_key_here>"
```

Catatan penting
- Jangan meng-commit API key ke git. Jika Anda sudah menaruh key di chat atau di tempat publik, pertimbangkan untuk merotasi/ubah key.
- Jika Anda ingin menggunakan layanan selain NewsAPI.org, ubah URL endpoint di `index.php` dan struktur parsing sesuai dokumentasi provider Anda.

Lisensi: ini contoh kecil untuk belajar.
