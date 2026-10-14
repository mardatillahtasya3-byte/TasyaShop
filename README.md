
# TasyaShop - Aplikasi Manajemen Inventaris

## Deskripsi
TasyaShop adalah aplikasi berbasis PHP dan MySQL yang digunakan untuk mengelola data produk dan persediaan barang.

## Fitur Aplikasi
- Login pengguna
- Dashboard
- Menampilkan data produk
- Menambahkan produk
- Mengedit produk
- Menghapus produk
- Mengelola kategori produk
- Menampilkan harga, stok, dan stok minimum

## Teknologi yang Digunakan
- PHP
- MySQL
- HTML
- CSS
- XAMPP
- Visual Studio Code

## Struktur Folder
```text
inventory_app/
├── assets/
│   └── css/
│       └── style.css
├── config/
│   └── database.php
├── products/
│   ├── create.php
│   ├── edit.php
│   ├── delete.php
│   └── index.php
├── public/
│   ├── login.php
│   └── dashboard.php
├── index.php
└── README.md
```

## Cara Menjalankan Aplikasi
1. Instal dan buka XAMPP.
2. Aktifkan Apache dan MySQL.
3. Simpan proyek di folder `C:\xampp\htdocs\inventory_app`.
4. Buka phpMyAdmin melalui http://localhost/phpmyadmin.
5. Buat database bernama `inventory_db`.
6. Pastikan tabel database sudah dibuat sesuai kebutuhan aplikasi.
7. Periksa konfigurasi database di `config/database.php`.
8. Buka aplikasi melalui browser.

## Link Aplikasi
- Halaman utama: http://localhost/inventory_app/
- Login: http://localhost/inventory_app/public/login.php
- Dashboard: http://localhost/inventory_app/public/dashboard.php
- Data produk: http://localhost/inventory_app/products/index.php

## Cara Menggunakan
1. Buka aplikasi melalui halaman utama.
2. Login menggunakan akun yang sudah terdaftar.
3. Setelah berhasil login, pengguna masuk ke Dashboard.
4. Buka halaman Data Produk.
5. Klik Tambah Produk untuk menambahkan produk.
6. Klik Edit untuk memperbarui data produk.
7. Klik Hapus untuk menghapus produk setelah konfirmasi.

## Konfigurasi Database
Database yang digunakan adalah `inventory_db`.

File koneksi database:
`config/database.php`

Pastikan koneksi PDO menggunakan nama database, username, dan password MySQL yang sesuai.

## Pemecahan Masalah
- Jika halaman tidak terbuka, pastikan Apache aktif.
- Jika database gagal terhubung, pastikan MySQL aktif dan konfigurasi database benar.
- Jika tombol Edit atau Hapus gagal, periksa file `edit.php`, `delete.php`, serta struktur tabel database.
- Jika login gagal, pastikan akun pengguna tersedia di tabel `users`.

## Keamanan
- Jangan membagikan password database.
- Gunakan password yang aman.
- Pastikan halaman Dashboard hanya dapat diakses setelah login.
- Validasi input sebelum menyimpan data ke database.

## Pengembang
TasyaShop - Aplikasi Manajemen Inventaris.
