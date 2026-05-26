# Plan: Hapus Fonnte → WhatsAppService (wweb.js only)

**Target**: Menghapus semua referensi "Fonnte", rename `FonnteService` → `WhatsAppService`, default gateway URL ke `http://localhost:3000`.

**Nama service**: `WhatsAppService`  
**Webhook routes**: Ganti total (`/webhook/fonnte/*` → `/webhook/whatsapp/*`)

---

## Step 1: Rename config

| File | Action |
|------|--------|
| `config/fonnte.php` → `config/whatsapp.php` | Rename file |

```php
// config/whatsapp.php
<?php
return [
    'webhook_secret' => env('WEBHOOK_SECRET'),
];
```

---

## Step 2: Rename service class

| File | Action |
|------|--------|
| `app/Services/FonnteService.php` → `app/Services/WhatsAppService.php` | Rename file, update class name, env refs, error messages |

Changes inside the file:
- `class FonnteService` → `class WhatsAppService`
- `$this->token = $device && $device->token ? $device->token : env('FONNTE_TOKEN');` → `$this->token = $device && $device->token ? $device->token : null;`
- `$this->baseUrl = $device && $device->gateway_url ? $device->gateway_url : env('WHATSAPP_GATEWAY_URL', 'https://api.fonnte.com');` → `$this->baseUrl = $device && $device->gateway_url ? $device->gateway_url : env('WHATSAPP_GATEWAY_URL', 'http://localhost:3000');`
- `Log::error('Fonnte getDeviceStatus error: '...` → `Log::error('WhatsApp getDeviceStatus error: '...`
- `'Gagal menghubungi Fonnte API.'` → `'Gagal menghubungi WhatsApp Gateway.'`
- `Log::error('Fonnte getQr error: '...` → `Log::error('WhatsApp getQr error: '...`
- PHPDoc: `Get Device details/status from Fonnte` → `Get Device details/status from WhatsApp Gateway`

---

## Step 3: Update all controllers (8 files)

### 3a. `app/Http/Controllers/WebhookController.php`
- `use App\Services\FonnteService;` → `use App\Services\WhatsAppService;`
- `config('fonnte.webhook_secret')` → `config('whatsapp.webhook_secret')` (line 19)
- Comment `* Handle device status webhook from Fonnte` → `* Handle device status webhook`
- `'Fonnte Device Webhook is online...'` → `'WhatsApp Device Webhook is online...'`
- `Log::info('Fonnte Device Webhook received'...` → `Log::info('WhatsApp Device Webhook received'...`
- `public function message(Request $request, FonnteService $fonnte)` → `public function message(Request $request, WhatsAppService $whatsapp)`
- All `$fonnte->` → `$whatsapp->` (lines 122, 167, 192, 202, 212, 251, 283, 308, 322)
- `'Fonnte Message Webhook is online...'` → `'WhatsApp Message Webhook is online...'`
- `Log::info('Fonnte Message Webhook received'...` → `Log::info('WhatsApp Message Webhook received'...`

### 3b. `app/Http/Controllers/CampaignController.php`
- `use App\Services\FonnteService;` → `use App\Services\WhatsAppService;`
- `FonnteService $fonnte` → `WhatsAppService $whatsapp` (lines 25, 335)
- `$fonnte->sendBulkMessages(...)` → `$whatsapp->sendBulkMessages(...)` (lines 237, 556)
- `'Gagal mengirim kampanye. Fonnte API Error.'` → `'Gagal mengirim kampanye. WhatsApp Gateway Error.'` (lines 248, 567)

### 3c. `app/Http/Controllers/SettingController.php`
- `use App\Services\FonnteService;` → `use App\Services\WhatsAppService;`
- `FonnteService $fonnte` → `WhatsAppService $whatsapp` (lines 11, 111)
- `env('FONNTE_TOKEN') ?? ''` → `''` (line 20 — no more env fallback)
- Comment `// Sync status from Fonnte API` → `// Sync status from WhatsApp Gateway`
- `$fonnte->setToken(...)` → `$whatsapp->setToken(...)` (lines 32, 57, 114)
- `$fonnte->getDeviceStatus()` → `$whatsapp->getDeviceStatus()` (line 33)
- `$fonnte->getQr()` → `$whatsapp->getQr()` (line 58)
- `$fonnte->disconnect()` → `$whatsapp->disconnect()` (line 116)
- `'gateway_url' => $validated['gateway_url'] ?? 'https://api.fonnte.com'` → `'gateway_url' => $validated['gateway_url'] ?? 'http://localhost:3000'` (lines 87, 104)

### 3d. `app/Http/Controllers/ContactController.php`
- `use App\Services\FonnteService;` → `use App\Services\WhatsAppService;`
- `FonnteService $fonnte` → `WhatsAppService $whatsapp` (lines 255, 297)
- `$fonnte->sendMessage(...)` → `$whatsapp->sendMessage(...)` (lines 275, 312)
- `'Gagal mengirim pesan langsung: Fonnte API Error.'` → `'Gagal mengirim pesan langsung: WhatsApp Gateway Error.'` (line 331)

### 3e. `app/Http/Controllers/MessageLogController.php`
- `use App\Services\FonnteService;` → `use App\Services\WhatsAppService;`
- `FonnteService $fonnte` → `WhatsAppService $whatsapp` (lines 163, 174)
- `$fonnte->sendMessage(...)` → `$whatsapp->sendMessage(...)` (lines 166, 184)

### 3f. `app/Http/Controllers/ApiController.php`
- `use App\Services\FonnteService;` → `use App\Services\WhatsAppService;`
- `FonnteService $fonnte` → `WhatsAppService $whatsapp` (line 45)
- `$fonnte->sendMessage(...)` → `$whatsapp->sendMessage(...)` (line 53)

### 3g. `app/Http/Controllers/DashboardController.php`
- `use App\Services\FonnteService;` → `use App\Services\WhatsAppService;`
- `FonnteService $fonnte` → `WhatsAppService $whatsapp` (line 13)

### 3h. `app/Console/Commands/DispatchCampaigns.php`
- `use App\Services\FonnteService;` → `use App\Services\WhatsAppService;`
- `FonnteService $fonnte` → `WhatsAppService $whatsapp` (lines 20, 63)
- `$this->dispatchCampaign($campaign, $fonnte)` → `$this->dispatchCampaign($campaign, $whatsapp)` (line 57)
- `$fonnte->setToken(...)` → `$whatsapp->setToken(...)` (line 137)
- `$fonnte->sendBulkMessages(...)` → `$whatsapp->sendBulkMessages(...)` (line 138)

---

## Step 4: Update routes

| File | Current | New |
|------|---------|-----|
| `routes/web.php:111` | `/webhook/fonnte/device` | `/webhook/whatsapp/device` |
| `routes/web.php:112` | `->name('webhook.fonnte.device')` | `->name('webhook.whatsapp.device')` |
| `routes/web.php:114` | `/webhook/fonnte/message` | `/webhook/whatsapp/message` |
| `routes/web.php:115` | `->name('webhook.fonnte.message')` | `->name('webhook.whatsapp.message')` |

---

## Step 5: Update Blade view

**File**: `resources/views/settings.blade.php`

| Line | Change |
|------|--------|
| 78 | `$dev->gateway_url ?? 'https://api.fonnte.com'` → `$dev->gateway_url ?? 'http://localhost:3000'` |
| 129 | `url('/webhook/fonnte/device')` → `url('/webhook/whatsapp/device')` |
| 138 | `url('/webhook/fonnte/message')` → `url('/webhook/whatsapp/message')` |
| 228 | Label: `Fonnte API Token` → `WhatsApp Token` |
| 229 | Placeholder: `Masukkan Token Fonnte...` → `Masukkan Token WhatsApp...` |
| 234 | Default value: `https://api.fonnte.com` → `http://localhost:3000` |
| 234 | Placeholder: `https://api.fonnte.com atau http://localhost:3000` → `http://localhost:3000` |
| 236 | Help text: `Gunakan https://api.fonnte.com untuk Fonnte...` → `Gunakan http://localhost:3000 untuk self-hosted gateway.` |
| 273 | Label edit modal: `Fonnte API Token` → `WhatsApp Token` |
| 279 | Placeholder: `https://api.fonnte.com atau http://localhost:3000` → `http://localhost:3000` |
| 281 | Help text: same as line 236 |

---

## Step 6: Update migration (for fresh installs)

**File**: `database/migrations/2026_05_21_000000_add_gateway_url_to_devices_table.php`

- Line 15: `->default('https://api.fonnte.com')` → `->default('http://localhost:3000')`

---

## Step 7: Update env files

**File**: `.env.example`
- Line 67: Remove `FONNTE_TOKEN=your_fonnte_token_here`
- Line 68: `WHATSAPP_GATEWAY_URL=https://api.fonnte.com` → `WHATSAPP_GATEWAY_URL=http://localhost:3000`

**File**: `.env`
- No changes needed (no FONNTE_TOKEN or api.fonnte.com present)

---

## Step 8: Update WhatsApp Gateway

**File**: `whatsapp-gateway/server.js`
- Line 15: `'http://127.0.0.1:8000/webhook/fonnte'` → `'http://127.0.0.1:8000/webhook/whatsapp'`
- Lines 443, 463: `mimicking Fonnte` → `(gateway async)` (komentar opsional, tidak kritis)

**File**: `whatsapp-gateway/.env`
- Line 2: `/webhook/fonnte` → `/webhook/whatsapp`
- Line 3: `/webhook/fonnte` → `/webhook/whatsapp` (di komentar)

---

## Step 9: Update tests (4 files)

**File**: `tests/TestCase.php`
- Line 27: `config('fonnte.webhook_secret')` → `config('whatsapp.webhook_secret')`

**File**: `tests/Feature/AutoReplyManagementTest.php`
- Line 7: `use App\Services\FonnteService;` → `use App\Services\WhatsAppService;`
- Line 72: `$this->mock(FonnteService::class, ...` → `$this->mock(WhatsAppService::class, ...`
- Line 88: `route('webhook.fonnte.message')` → `route('webhook.whatsapp.message')`

**File**: `tests/Feature/AdvancedFeaturesTest.php`
- Line 11: `use App\Services\FonnteService;` → `use App\Services\WhatsAppService;`
- Line 100: `$this->mock(FonnteService::class, ...` → `$this->mock(WhatsAppService::class, ...`
- Line 109: `route('webhook.fonnte.message')` → `route('webhook.whatsapp.message')`
- Line 191: Comment `// Mock FonnteService. Fonnte should...` → `// Mock WhatsAppService`
- Line 195: `$this->mock(FonnteService::class, ...` → `$this->mock(WhatsAppService::class, ...`

**File**: `tests/Feature/CampaignCsvTest.php`
- Line 6: `use App\Services\FonnteService;` → `use App\Services\WhatsAppService;`
- Line 17: Comment `// Mock FonnteService to prevent...` → `// Mock WhatsAppService to prevent...`
- Line 18: `$this->mock(FonnteService::class, ...` → `$this->mock(WhatsAppService::class, ...`

---

## Step 10: Update documentation

**File**: `AGENTS.md`
- Line 14: `api.fonnte.com` → `localhost:3000`
- Line 60: `FONNTE_TOKEN` → hapus dari daftar env

**File**: `walkthrough.md`
- Line 3: `dari Fonnte API` → `ke WhatsApp Web Gateway`
- Line 12: `Fonnte API` → `WhatsApp Gateway API`
- Line 19: `/webhook/fonnte/message` → `/webhook/whatsapp/message`
- Line 22: `https://api.fonnte.com` → `http://localhost:3000`
- Line 23: `FonnteService.php` → `WhatsAppService.php`
- Line 27: `FonnteService.php` → `WhatsAppService.php`

---

## Step 11: Clear caches

```bash
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

Delete compiled view: `storage/framework/views/03d7c08962ebd4050e179e8d49e52e64.php`

---

## Step 12: Verify

```bash
composer run test              # All 22 tests must PASS
php artisan route:list --name=webhook  # Verify new route names exist
vendor/bin/pint --dirty        # Format code
```

---

## File change summary

| Step | Files | Type |
|------|-------|------|
| 1 | 1 | Rename config |
| 2 | 1 | Rename service class |
| 3 | 8 | Controllers + command (imports, type-hints, strings) |
| 4 | 1 | Routes |
| 5 | 1 | Blade view |
| 6 | 1 | Migration default |
| 7 | 2 | Env files |
| 8 | 2 | WhatsApp gateway |
| 9 | 4 | PHPUnit tests |
| 10 | 2 | Documentation |
| 11 | — | Commands only (cache clear) |
| 12 | — | Commands only (verify tests) |

**Total: 22 file + 3 artisan commands**
