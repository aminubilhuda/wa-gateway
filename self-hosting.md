# Self-Hosting Guide — WA-BLAST

Panduan lengkap deployment aplikasi WA-BLAST (Laravel + WhatsApp Gateway) ke server sendiri menggunakan **aaPanel**, **Cloudflare**, dan **Cloudflare Zero Trust Tunnel**.

---

## Arsitektur Production

```
Internet → Cloudflare (SSL/CDN) → Cloudflare Tunnel → Server (aaPanel)
                                                      ├── Nginx → Laravel App
                                                      ├── PM2 → WhatsApp Gateway (port 3000)
                                                      ├── Cron → Scheduler (schedule:run)
                                                      └── Supervisor → Queue Worker
```

---

## Prerequisites

- **Server**: VPS minimal 2GB RAM, 2 vCPU, 20GB SSD (Ubuntu 22.04/24.04 LTS)
- **Domain**: Domain sendiri (misal: `wablast.example.com`)
- **Cloudflare Account**: Gratis sudah cukup
- **aaPanel**: Free version

---

## Langkah 1 — Setup Cloudflare DNS

1. Login ke [Cloudflare Dashboard](https://dash.cloudflare.com)
2. **Add a Site** → masukkan domain kamu
3. Ikuti instruksi untuk **ubah nameserver** domain ke Cloudflare
4. Tunggu propagasi DNS (biasanya 5-30 menit)
5. Di menu **DNS** → **Records**, buat record:

| Type | Name | Content | Proxy Status |
|------|------|---------|--------------|
| A | `wablast` | `<IP Server>` | DNS only (abu-abu) |

> **Catatan**: Proxy status akan otomatis berubah ke **Proxied** (orange) setelah Tunnel aktif.

---

## Langkah 2 — Install aaPanel

### 2.1 Login ke Server via SSH
```bash
ssh root@<IP_SERVER>
```

### 2.2 Install aaPanel
```bash
# Ubuntu/Debian
wget -O install.sh http://www.aapanel.com/script/install-ubuntu_6.0_en.sh && bash install.sh aapanel

# CentOS/RHEL
yum install -y wget && wget -O install.sh http://www.aapanel.com/script/install_6.0_en.sh && bash install.sh aapanel
```

### 2.3 Akses Panel
Setelah install selesai, cat URL, username, dan password yang ditampilkan. Buka di browser:
```
http://<IP_SERVER>:8888/<random_path>
```

### 2.4 Install Stack
Di aaPanel → **App Store** → install:

| Package | Versi | Keterangan |
|---------|-------|------------|
| **Nginx** | 1.24+ | Web server |
| **PHP** | 8.3+ | Runtime Laravel |
| **Node.js** | 18+ | Untuk WhatsApp Gateway |
| **PM2 Manager** | Latest | Process manager untuk Node.js |
| **Supervisor** | Latest | Process manager untuk Queue Worker |
| **Redis** | Latest | Opsional, untuk cache |

### 2.5 Install PHP Extensions
Di aaPanel → **App Store** → **PHP 8.x** → **Install Extensions**:
- `sqlite3`
- `mbstring`
- `xml`
- `curl`
- `zip`
- `gd`
- `fileinfo`
- `bcmath`
- `tokenizer`
- `json`
- `ctype`

---

## Langkah 3 — Setup Project Laravel

### 3.1 Buat Website di aaPanel
1. Menu **Website** → **Add Site**
2. Isi:
   - **Domain**: `wablast.example.com`
   - **Root Directory**: `/www/wwwroot/wablast`
   - **PHP Version**: `8.3`
   - **Database**: Tidak perlu (kita pakai SQLite)
3. Klik **Submit**

### 3.2 Upload Project
Via SSH:
```bash
cd /www/wwwroot
rm -rf wablast  # hapus default folder dari aaPanel

# Clone dari Git (jika ada)
git clone <REPO_URL> wablast

# ATAU upload manual via SCP/SFTP
# scp -r D:\PROJECT\WA-BLAST root@<IP>:/www/wwwroot/wablast
```

### 3.3 Install Dependencies
```bash
cd /www/wwwroot/wablast

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies (untuk build assets)
npm install
npm run build
```

### 3.4 Setup Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env`:
```env
APP_NAME=WA-BLAST
APP_ENV=production
APP_DEBUG=false
APP_URL=https://wablast.example.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug

DB_CONNECTION=sqlite
# Hapus atau comment DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=log

WHATSAPP_GATEWAY_URL=http://127.0.0.1:3000
FONNTE_TOKEN=Dvaqgkkt9Gvuu7iGrmsc
```

### 3.5 Setup Database SQLite
```bash
touch /www/wwwroot/wablast/database/database.sqlite
chown www-data:www-data /www/wwwroot/wablast/database/database.sqlite
php artisan migrate --force
```

### 3.6 Set Permissions
```bash
chown -R www-data:www-data /www/wwwroot/wablast/storage
chown -R www-data:www-data /www/wwwroot/wablast/bootstrap/cache
chmod -R 775 /www/wwwroot/wablast/storage
chmod -R 775 /www/wwwroot/wablast/bootstrap/cache
```

### 3.7 Buat Folder Uploads
```bash
mkdir -p /www/wwwroot/wablast/public/uploads/attachments
chown -R www-data:www-data /www/wwwroot/wablast/public/uploads
```

---

## Langkah 4 — Setup Nginx

Di aaPanel → **Website** → klik domain `wablast.example.com` → **Config**

Ganti seluruh isi config dengan:

```nginx
server {
    listen 80;
    server_name wablast.example.com;
    root /www/wwwroot/wablast/public;
    index index.php index.html;

    # Max upload size 16MB
    client_max_body_size 20M;

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-83.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }

    # Webhook endpoint
    location /webhook/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Static assets cache
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Block sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(env|git|sqlite|log)$ {
        deny all;
    }

    # Block composer/vendor
    location ~ /vendor/ {
        deny all;
    }
}
```

> **Note**: Sesuaikan `php-cgi-83.sock` dengan versi PHP yang diinstall. Cek di `/tmp/` untuk nama socket yang benar.

---

## Langkah 5 — Deploy WhatsApp Gateway

### 5.1 Install Dependencies Gateway
```bash
cd /www/wwwroot/wablast/whatsapp-gateway
npm install --production
```

### 5.2 Setup .env Gateway
```bash
cat > /www/wwwroot/wablast/whatsapp-gateway/.env << 'EOF'
GATEWAY_PORT=3000
LARAVEL_WEBHOOK_URL=https://wablast.example.com/webhook/fonnte
EOF
```

### 5.3 Install PM2 (jika belum)
```bash
npm install -g pm2
```

### 5.4 Start Gateway dengan PM2
```bash
cd /www/wwwroot/wablast/whatsapp-gateway
pm2 start server.js --name wa-gateway --max-memory-restart 500M
pm2 save
pm2 startup
```

### 5.5 Verifikasi
```bash
pm2 list
# Harus tampil: wa-gateway | online
pm2 logs wa-gateway
# Cek log gateway berjalan normal
```

---

## Langkah 6 — Setup Queue Worker

### 6.1 Install Supervisor
```bash
apt update && apt install -y supervisor
```

### 6.2 Buat Config Worker
```bash
cat > /etc/supervisor/conf.d/laravel-worker.conf << 'EOF'
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/wablast/artisan queue:work --sleep=1 --tries=3 --timeout=120
directory=/www/wwwroot/wablast
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/www/wwwroot/wablast/storage/logs/worker.log
stopwaitsecs=3600
EOF
```

### 6.3 Start Supervisor
```bash
supervisorctl reread
supervisorctl update
supervisorctl start laravel-worker:*
supervisorctl status
```

---

## Langkah 7 — Setup Scheduler (Cron)

### 7.1 Via aaPanel Cron
1. Menu **Cron** → **Add Cron**
2. Isi:
   - **Type**: `Shell Script`
   - **Name**: `Laravel Scheduler`
   - **Schedule**: `N Minute` → `Every 1 Minute`
   - **Script**:
     ```bash
     cd /www/wwwroot/wablast && php artisan schedule:run >> /dev/null 2>&1
     ```
3. Klik **Add**

### 7.2 Atau Via Terminal
```bash
crontab -e
```

Tambahkan baris:
```cron
* * * * * cd /www/wwwroot/wablast && php artisan schedule:run >> /dev/null 2>&1
```

---

## Langkah 8 — Setup Cloudflare Tunnel (Zero Trust)

### 8.1 Install cloudflared
```bash
# Download binary
curl -fsSL https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -o /usr/local/bin/cloudflared
chmod +x /usr/local/bin/cloudflared

# Verifikasi
cloudflared --version
```

### 8.2 Login ke Cloudflare
```bash
cloudflared tunnel login
```
Akan muncul URL → buka di browser → login Cloudflare → pilih domain → **Allow**.

### 8.3 Buat Tunnel
```bash
cloudflared tunnel create wablast-tunnel
```

Catat **Tunnel ID** yang muncul (format: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`).

### 8.4 Route DNS ke Tunnel
```bash
cloudflared tunnel route dns wablast-tunnel wablast.example.com
```

### 8.5 Buat Config Tunnel
```bash
mkdir -p /etc/cloudflared

cat > /etc/cloudflared/config.yml << EOF
tunnel: <TUNNEL-ID>
credentials-file: /root/.cloudflared/<TUNNEL-ID>.json

ingress:
  - hostname: wablast.example.com
    service: http://localhost:80
  - service: http_status:404
EOF
```

> Ganti `<TUNNEL-ID>` dengan ID tunnel yang dicatat di langkah 8.3.

### 8.6 Test Tunnel
```bash
cloudflared tunnel --config /etc/cloudflared/config.yml run
```
Buka `https://wablast.example.com` di browser — harusnya sudah bisa diakses.

Tekan `Ctrl+C` untuk stop.

### 8.7 Install sebagai Systemd Service
```bash
cloudflared service install
systemctl enable cloudflared
systemctl start cloudflared
systemctl status cloudflared
```

### 8.8 Verifikasi Tunnel
Di Cloudflare Dashboard → **Zero Trust** → **Networks** → **Tunnels**:
- Harus muncul `wablast-tunnel` dengan status **Healthy**

---

## Langkah 9 — Setup SSL di Cloudflare

Di Cloudflare Dashboard:

### 9.1 SSL/TLS Mode
- Menu **SSL/TLS** → **Overview**
- Pilih **Full (strict)**

### 9.2 Edge Certificates
- Menu **SSL/TLS** → **Edge Certificates**
- Aktifkan:
  - **Always Use HTTPS**: ON
  - **Minimum TLS Version**: TLS 1.2
  - **Automatic HTTPS Rewrites**: ON
  - **Opportunistic Encryption**: ON

### 9.3 DNS Proxy
- Menu **DNS** → **Records**
- Pastikan record `wablast.example.com` statusnya **Proxied** (orange cloud)

---

## Langkah 10 — Setup User & Login Pertama

### 10.1 Buat User Admin
Buka `https://wablast.example.com/register` dan buat akun admin pertama.

### 10.2 Setup Device & QR Code
1. Login ke dashboard
2. Menu **Settings** → setup device
3. Scan QR code dengan WhatsApp

> **Catatan**: Setelah scan QR, session tersimpan di `whatsapp-gateway/.wwebjs_auth/` dan akan persist walau server restart.

---

## Langkah 11 — Final Checklist

| Item | Cara Cek | Status |
|------|----------|--------|
| Laravel app running | `curl -I https://wablast.example.com` → HTTP 200 | ☐ |
| Nginx serving `/public` | Cek config Nginx | ☐ |
| WhatsApp Gateway via PM2 | `pm2 list` → `wa-gateway` online | ☐ |
| Queue worker running | `supervisorctl status` → RUNNING | ☐ |
| Scheduler (cron) running | `crontab -l` → ada entry schedule:run | ☐ |
| Cloudflare Tunnel active | `systemctl status cloudflared` → active | ☐ |
| SSL/HTTPS enabled | `https://wablast.example.com` → gembok hijau | ☐ |
| `.env` `APP_DEBUG=false` | `cat .env \| grep APP_DEBUG` | ☐ |
| Webhook accessible | `curl https://wablast.example.com/webhook/fonnte/device` | ☐ |
| Database migrated | `php artisan migrate:status` → all ran | ☐ |
| Storage permissions | `ls -la storage/` → owned by www-data | ☐ |

---

## Troubleshooting

### Gateway tidak bisa connect
```bash
pm2 logs wa-gateway
# Cek error di log
pm2 restart wa-gateway
```

### Campaign tidak terkirim otomatis
```bash
# Cek cron
crontab -l

# Cek scheduler log
tail -f /www/wwwroot/wablast/storage/logs/laravel.log

# Manual test
cd /www/wwwroot/wablast && php artisan campaigns:dispatch
```

### Queue worker stuck
```bash
supervisorctl restart laravel-worker:*
```

### Cloudflare Tunnel down
```bash
systemctl restart cloudflared
systemctl status cloudflared
journalctl -u cloudflared -f
```

### Permission denied di storage
```bash
chown -R www-data:www-data /www/wwwroot/wablast/storage
chown -R www-data:www-data /www/wwwroot/wablast/bootstrap/cache
chmod -R 775 /www/wwwroot/wablast/storage
```

### File upload gagal
```bash
# Cek Nginx client_max_body_size
# Cek PHP upload_max_filesize (di aaPanel → PHP → Config)
upload_max_filesize = 20M
post_max_size = 20M
```

---

## Maintenance

### Backup Database
```bash
cp /www/wwwroot/wablast/database/database.sqlite /backup/database-$(date +%Y%m%d).sqlite
```

### Update Project
```bash
cd /www/wwwroot/wablast
git pull
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
```

### Restart Semua Service
```bash
pm2 restart wa-gateway
supervisorctl restart laravel-worker:*
systemctl restart cloudflared
systemctl restart nginx
```

### Cek Log
```bash
# Gateway log
pm2 logs wa-gateway

# Laravel log
tail -f /www/wwwroot/wablast/storage/logs/laravel.log

# Queue worker log
tail -f /www/wwwroot/wablast/storage/logs/worker.log

# Cloudflare tunnel log
journalctl -u cloudflared -f
```

---

## Port Reference

| Service | Port | Akses |
|---------|------|-------|
| Nginx | 80, 443 | Public (via Cloudflare) |
| Laravel | 80 (via Nginx) | Public (via Cloudflare) |
| WhatsApp Gateway | 3000 | Local only (127.0.0.1) |
| Cloudflare Tunnel | - | Outbound ke Cloudflare |
| aaPanel | 8888 | Private (IP langsung) |

> **Keamanan**: Jangan expose port 3000 ke internet. Gateway hanya diakses oleh Laravel via `http://127.0.0.1:3000`.
