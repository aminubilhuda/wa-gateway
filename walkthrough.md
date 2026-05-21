# Walkthrough Pengerjaan: Integrasi Self-Hosted WhatsApp Web Gateway (whatsapp-web.js)

Berikut adalah panduan lengkap pengerjaan dan konfigurasi untuk migrasi dari Fonnte API ke **WhatsApp Web Gateway mandiri (self-hosted)** menggunakan pustaka **whatsapp-web.js** di Node.js, beserta seluruh fitur lanjutan yang sudah terpasang sebelumnya.

---

## Ringkasan Perubahan & Fitur Terpasang

### 1. WhatsApp Web Gateway (Node.js) - Baru!
* **Aplikasi Gateway Mandiri**: Dibuat di dalam folder [whatsapp-gateway/](file:///d:/PROJECT/WA-BLAST/whatsapp-gateway) dengan komponen:
  * `package.json`: Menyimpan dependensi Express, whatsapp-web.js (v1.26.0), qrcode, axios, dan dotenv.
  * `server.js`: Menangani HTTP endpoints yang meniru format Fonnte API secara persis, mengelola sesi otentikasi multi-perangkat via `LocalAuth` (disimpan di subfolder `.wwebjs_auth/`), dan mengontrol alur pengiriman asinkron dengan jeda kirim kustom.
* **Integrasi dengan Laravel**:
  * Peta REST API:
    * `/device`: Mengecek status koneksi sesi.
    * `/qr`: Menginisialisasi client WhatsApp Web di latar belakang dan mengembalikan QR Code instan berbasis gambar base64 PNG yang dimuat langsung ke dashboard Settings.
    * `/disconnect`: Keluar dari sesi WhatsApp Web secara aman dan menghapus file otentikasi sesi di server.
    * `/send`: Mengirimkan pesan tunggal maupun antrean pesan massal (bulk) berurutan menggunakan kalkulasi delay dinamis.
  * **Webhook Terintegrasi**: Pesan masuk yang diterima oleh Node.js diteruskan secara otomatis ke Laravel Webhook (`/webhook/fonnte/message`) untuk auto-reply dan proses daftar hitam otomatis.

### 2. Penyelarasan di Laravel
* **Gateway Switcher Dinamis**: Menambahkan variabel `WHATSAPP_GATEWAY_URL` di berkas [.env](file:///d:/PROJECT/WA-BLAST/.env) dan [.env.example](file:///d:/PROJECT/WA-BLAST/.env.example). Secara default diatur ke `https://api.fonnte.com`, dan dapat dialihkan ke `http://127.0.0.1:3000` untuk mengaktifkan gateway mandiri.
* **Konstruktor FonnteService**: Diperbarui pada [FonnteService.php](file:///d:/PROJECT/WA-BLAST/app/Services/FonnteService.php) agar mengambil nilai base URL dinamis dari variabel `.env` tersebut.
* **Multi-Device Webhook Checker**: Diperbarui pada [WebhookController.php](file:///d:/PROJECT/WA-BLAST/app/Http/Controllers/WebhookController.php) agar pencocokan status koneksi perangkat dari webhook disesuaikan berdasarkan kecocokan token perangkat (bukan hanya memperbarui perangkat baris pertama).

### 3. Fitur Kelas Enterprise Lainnya (Sudah Berfungsi & Teruji)
* **Rotasi Multi-Perangkat (Round-Robin)**: Membagi daftar tujuan kampanye ke seluruh perangkat aktif secara bergiliran.
* **Templat Pesan (Message Templates)**: CRUD templat dan dropdown seleksi otomatis di form kampanye baru.
* **Manajemen Daftar Cekal (Blacklist)**: CRUD nomor blacklist dan otomatisasi blokir via perintah `STOP` / `UNSUBSCRIBE` lewat webhook.
* **Ekspor CSV Dinamis**: Ekspor laporan riil di halaman `/reports` yang menyesuaikan penyaringan parameter aktif.

---

## Petunjuk Penyebaran & Menjalankan di Server aaPanel (Produksi)

Untuk mendeploy gateway mandiri ini di server aaPanel Anda, ikuti langkah-langkah mudah berikut:

### Langkah 1: Siapkan Folder Gateway
Unggah seluruh folder `whatsapp-gateway/` ke server aaPanel Anda (misalnya ditempatkan sejajar dengan folder project laravel).

### Langkah 2: Install Node.js & Dependensi di aaPanel
1. Buka panel kontrol **aaPanel**.
2. Masuk ke menu **App Store** -> Cari dan instal **Node.js Version Manager** (jika belum terpasang).
3. Melalui Node.js Version Manager, instal versi Node.js stabil terbaru (direkomendasikan **v18** atau **v20**).
4. Buka Terminal Server aaPanel Anda, masuk ke direktori gateway, lalu jalankan instalasi paket:
   ```bash
   cd /www/wwwroot/nama-project-anda/whatsapp-gateway
   npm install
   ```

### Langkah 3: Install Dependensi Chromium (Penting untuk Linux Server)
Karena `whatsapp-web.js` menjalankan browser Chromium di latar belakang untuk merender WhatsApp Web, server Linux Anda memerlukan paket dependensi visual berikut. Jalankan perintah ini di Terminal aaPanel Anda (sebagai root):

* **Untuk Ubuntu / Debian**:
  ```bash
  sudo apt-get update
  sudo apt-get install -y libnss3 libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 libxkbcommon0 libxcomposite1 libxdamage1 libxrandr2 libgbm1 libasound2 libpango-1.0-0 libcairo2
  ```
* **Untuk CentOS / AlmaLinux**:
  ```bash
  sudo yum install -y alsa-lib atk cups-libs gtk3 libXcomposite libXcursor libXdamage libXext libXfixes libXi libXrandr libXscrnsim libXtst pango mesa-libGBM libxshmfence nss
  ```

### Langkah 4: Jalankan Gateway di Latar Belakang (PM2)
Agar server Node.js tetap berjalan terus-menerus di server aaPanel:
1. Di aaPanel Node.js Version Manager, instal **PM2** (Process Manager).
2. Atau jalankan perintah PM2 langsung di terminal dalam folder `whatsapp-gateway`:
   ```bash
   npm install -g pm2
   pm2 start server.js --name "whatsapp-gateway"
   pm2 save
   pm2 startup
   ```
   *(PM2 akan memastikan server Node.js otomatis menyala kembali jika server reboot/restart).*

### Langkah 5: Hubungkan Laravel dengan Gateway
1. Buka berkas `.env` dari aplikasi Laravel Anda di aaPanel.
2. Ubah baris konfigurasi gateway untuk mengarah ke alamat lokal server Node.js:
   ```env
   WHATSAPP_GATEWAY_URL=http://127.0.0.1:3000
   ```
3. Pastikan port `3000` telah terbuka di menu **Security** pada aaPanel (jika Laravel dan Node.js berjalan di server yang berbeda, namun jika di server yang sama menggunakan `127.0.0.1`, port tidak perlu dibuka ke publik demi keamanan).

---

## Hasil Pengujian & Verifikasi

* **Automated Tests (`php artisan test`)**:
  * **STATUS**: **`22 PASSED (117 assertions)`**. 
  * Seluruh pengujian otomatis (fitur kampanye, scheduler rotasi, auto-reply, webhook, dan ekspor) terkonfirmasi berjalan sukses tanpa ada regresi sistem.
* **Uji Jalan Gateway Lokal**:
  * Gateway Node.js berhasil dijalankan secara lokal di port `3000` dan siap meneruskan serta memproses seluruh panggilan API dari Laravel.
