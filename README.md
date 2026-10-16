TasyaShop — Sistem Manajemen Inventaris
TasyaShop adalah aplikasi web manajemen inventaris berbasis PHP native dan MySQL. Aplikasi ini dirancang untuk membantu mengelola data produk, kategori, pemasok, serta transaksi stok masuk dan keluar dalam satu tempat.
Fitur
- Login pengguna untuk mengakses halaman dashboard.
- Dashboard inventaris yang menampilkan ringkasan produk, kategori, total stok, serta peringatan stok menipis.
- Manajemen produk (CRUD): tambah, lihat, edit, dan hapus produk.
- Pencarian dan filter kategori pada daftar produk.
- Informasi pemasok dan harga pemasok yang terhubung ke produk.
- Transaksi stok masuk dan stok keluar.
- Pembaruan stok otomatis setelah transaksi stok disimpan.
- Riwayat pergerakan stok dengan tanggal, jenis transaksi, jumlah, dan catatan.
- Laporan persediaan berisi harga jual, stok, stok minimum, status persediaan, serta perkiraan nilai persediaan.
- Ekspor laporan CSV yang dapat dibuka menggunakan Microsoft Excel.
- Cetak laporan melalui dialog cetak browser atau simpan sebagai PDF.
- Antarmuka blue-grey yang responsif.
Teknologi
- PHP
- MySQL
- PDO untuk koneksi dan query database
- HTML
- CSS
- JavaScript untuk interaksi sederhana pada halaman
- XAMPP sebagai lingkungan pengembangan lokal
Persyaratan
- XAMPP atau lingkungan server lokal serupa
- PHP dengan dukungan PDO MySQL
- MySQL atau MariaDB
- Browser web modern
Instalasi Lokal
1. Jalankan Apache dan MySQL melalui XAMPP Control Panel.
2. Salin folder proyek ke direktori web server, contohnya:
   C:\xampp\htdocs\inventory_app
3. Buat database bernama inventory_db melalui phpMyAdmin.
4. Siapkan tabel-tabel yang dibutuhkan aplikasi menggunakan berkas SQL skema/database proyek, jika tersedia.
5. Buka config/database.php, lalu sesuaikan host, nama database, username, dan password MySQL dengan konfigurasi lokal.
6. Pastikan tabel dan relasi yang digunakan aplikasi tersedia, termasuk tabel produk, kategori, pengguna, pemasok, hubungan produk-pemasok, dan riwayat pergerakan stok.
7. Buka halaman login melalui alamat berikut:
   http://localhost/inventory_app/public/login.php
Catatan: Kredensial login bergantung pada data pengguna di database. README ini tidak menetapkan username atau password bawaan. Jangan unggah password database atau kredensial rahasia ke repositori publik.

Halaman Utama
Dengan struktur proyek lokal di atas, halaman utama aplikasi dapat dibuka melalui:
- Login: http://localhost/inventory_app/public/login.php
- Dashboard: http://localhost/inventory_app/public/dashboard.php
- Produk: http://localhost/inventory_app/products/index.php
- Riwayat stok: http://localhost/inventory_app/stock_movements/index.php
- Laporan persediaan: http://localhost/inventory_app/reports/index.php
Laporan Persediaan
Laporan menampilkan stok dan perkiraan nilai persediaan yang dihitung menggunakan rumus:
Nilai persediaan = Harga jual × Jumlah stok
Nilai tersebut adalah perkiraan berdasarkan harga jual produk, bukan nilai modal atau harga pembelian dari pemasok.
Fitur ekspor menghasilkan berkas CSV yang dapat dibuka di Microsoft Excel. Untuk menyimpan sebagai berkas .xlsx, buka CSV tersebut di Excel lalu gunakan Save As → Excel Workbook (.xlsx).
Keamanan dan Catatan Pengembangan
- Gunakan password database yang sesuai dengan lingkungan lokal.
- Hindari mengunggah kredensial, data pribadi, atau data transaksi sensitif ke GitHub.
- Sebelum mengunggah proyek, periksa perubahan dengan git status dan pastikan file rahasia tidak ikut di-commit.
- Lakukan pencadangan database secara berkala.
- Fitur dan tampilan dapat disesuaikan lebih lanjut sesuai kebutuhan toko.
Pengembang
TasyaShop — proyek aplikasi web manajemen inventaris.