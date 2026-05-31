# Visi — Roadmap & Task List

> **Tujuan dokumen ini:** snapshot status implementasi vs PRD. Setiap task punya status, file utama, dan acceptance criteria singkat. AI / developer lain bisa lanjutin tanpa rombak — tinggal cari task `STATUS: TODO` berikutnya.

PRD sumber: `+PRD.txt` (lihat lampiran sesi atau request ulang).
Strategi MVP: **Path A — Single-tenant** (tenant tetap `acoustic-nights`, drop public *tenant* signup / onboarding). Schema tetap multi-tenant ready. Catatan: akun *customer* (pembeli tiket) tetap ada — lihat 4.0.

Stack saat ini: PHP 8.1 (no framework, custom MVC), MariaDB 10.6, CoreUI Bootstrap 5.4.1 (CDN). Tidak ada build step.

---

## Legenda status

- `DONE` — sudah jadi & ter-smoke-test.
- `WIRED` — fungsi/route ada, UI ada, tapi belum end-to-end (mis. UI mock, backend belum nyimpen).
- `STUB` — placeholder UI saja, belum ada logic backend.
- `TODO` — belum ada.
- `BLOCKED` — perlu kredensial / keputusan eksternal (PG, SMTP, Fonnte, dll.).

---

## Phase 1 — Single-tenant MVP

### 1. Pondasi & Infra

- [x] **DONE** — Stack PHP MVC + MySQL + Router custom (`public/index.php`, `src/Router.php`).
- [x] **DONE** — Skema DB lengkap (`database/schema.sql`): tenants, users, venues, events, seat_maps, seats, ticket_categories, seat_holds, orders, order_items, tickets, payments, promotions, webhook_logs, ticket_scans, refunds.
- [x] **DONE** — Seed demo: tenant `acoustic-nights`, admin `admin@acousticnights.com` / `password`, 2 event sample (`database/seed.sql`).
- [x] **DONE** — Auth + session (login/logout) + middleware `authMiddleware`, `adminMiddleware`, `tenantMiddleware` (`src/Controllers/AuthController.php`, `public/index.php`).
- [x] **DONE** — Onboarding publik di-disable → redirect `/onboarding` ke `/login` (Path A).
- [x] **DONE** — Flash message helper (`src/Session.php` `flash()` / `consumeFlash()`), dipakai oleh semua admin form save.
- [x] **DONE** — `.env` loader resmi (`src/Env.php`, di-load di `public/index.php` sebelum config). Mendukung quote, escape, interpolasi `${VAR}`, dan tidak menimpa env OS. Template di `.env.example`.
- [x] **DONE** — Migrasi tooling: `php migrate.php up` / `php migrate.php status`. Riwayat di tabel `schema_migrations`, baca `database/migrations/*.sql` urut natural. Migration 001 dibuat idempotent (cek `information_schema`).
- [ ] **TODO** — Tooling lint/test (PHPStan/Psalm + PHPUnit). Sekarang nol.
- [ ] **TODO** — CI (GitHub Actions): lint + test on PR. Sekarang nol.
- [x] **DONE** — Audit log viewer di admin (`/admin/logs`, 3 tab: Notifikasi/Webhook/Scan). Tab Notifikasi punya tombol "Kirim ulang" untuk notif gagal. Badge counter notif/webhook gagal.

### 2. UI / Theming

- [x] **DONE** — CoreUI Bootstrap 5.4.1 (CDN). 1 sistem styling. Tailwind dibuang. (`views/layouts/*`)
- [x] **DONE** — Dark/Light/Auto toggle dengan localStorage `visi-theme` (`assets/js/color-modes.js`).
- [x] **DONE** — Sidebar admin 3 grup (Operasional / Insight / Setup) + CoreUI `cil-*` icons.
- [x] **DONE** — Visi brand orange (`#f97316` / `#fb923c` dark) override CoreUI primary (`assets/css/visi.css`).
- [x] **DONE** — Seat map theme-aware (CSS vars per `[data-coreui-theme]`).
- [x] **DONE** — Tenant branding actually applied: `src/Branding.php` baca `tenants.branding.primary_color` → inject `<style>` override `--cui-primary` (+ turunan hover/active & seat-selected) di layout admin & publik. Settings color picker nyimpen & ke-apply.
- [x] **DONE** — Logo tenant di header public & admin. Upload di Settings (`handleLogoUpload`, validasi MIME + maks 2MB, simpan ke `/uploads/branding/`), tampil di sidebar admin & header publik. Fallback ke nama/inisial kalau belum ada logo.

### 3. Admin Panel

#### 3.1 Dashboard

- [x] **DONE** — Stat cards (penjualan hari ini, order pending, tiket terjual, total order) + tabel "Pesanan Terbaru" (`views/admin/dashboard.php`).
- [x] **DONE** — Chart penjualan 7/30 hari (Chart.js, line chart dual-axis: total penjualan + jumlah order, zero-filled, theme-aware). Empty state kalau belum ada penjualan.
- [x] **DONE** — Filter periode di dashboard (toggle 7/30 hari via `?days=`, fallback aman).

#### 3.2 Event Management

- [x] **DONE** — List events (`/admin/events` → `views/admin/events.php`).
- [x] **DONE** — Editor event (`/admin/events/create`, `/admin/events/{id}`) jadi real form, submit ke `POST /admin/events[/{id}]`. Simpan `events.{title,description,venue_id,start_time,end_time,status,settings}` + replace seat map (kecuali ada seat yang sudah sold di mode edit → safeguard, layout tidak dirombak).
- [x] **DONE** — Tombol "Simpan Draft" / "Publish" set `status='draft'` / `'published'`.
- [x] **DONE** — Cancel event via `POST /admin/events/{id}/delete` → set `status='cancelled'`.
- [x] **DONE** — Page CRUD ticket categories (`/admin/ticket-categories`): inline edit row + create modal + delete (guard: tolak hapus jika dipakai seat). Lihat **3.10**.
- [ ] **TODO** — Soft-delete event (kolom `deleted_at` perlu ditambah ke schema). Saat ini hanya status='cancelled'.

#### 3.3 Venue Management

- [x] **DONE** — Route `/admin/venues` (list dengan count event terkait), `/admin/venues/create`, `/admin/venues/{id}` (form edit), `POST /admin/venues/{id}/delete` (dengan guard: tidak bisa hapus venue yang masih dipakai event).

#### 3.4 Orders (Pesanan)

- [x] **DONE** — List orders (`/admin/orders` → `views/admin/orders.php`).
- [x] **DONE** — Detail order page (`/admin/orders/{id}`) — items + tickets + refunds + customer + event + payment cards.
- [x] **DONE** — Export CSV (`GET /admin/orders/export`) — UTF-8 BOM, stream langsung, kolom code/status/total/customer/event.
- [x] **DONE** — Refund (stub PG): `POST /admin/orders/{id}/refund` insert ke `refunds`, set `orders.status='refunded'`, `tickets.status='refunded'`, lepas seat ke `available`. Saldo real di PG masih BLOCKED.
- [x] **DONE** — Filter status, tanggal, search (`/admin/orders?status=&from=&to=&q=`). Shared filter builder dipakai juga oleh export CSV (export ikut filter aktif).

#### 3.5 Customer (BARU di PR #2)

- [x] **DONE** — List customer agregat by `COALESCE(user_id, customer_email)` (`/admin/customers`).
- [x] **DONE** — Detail customer + histori order (`/admin/customers/{id}` atau `/admin/customers/email:{email}`).
- [x] **DONE** — Telepon pakai `COALESCE(u.phone, MAX(o.customer_phone))` (fix commit `05e09c5`).
- [x] **DONE** — Search di list (server-side via `?q=`, match nama/email/telepon pakai HAVING di query agregat).
- [x] **DONE** — Export CSV daftar customer (`/admin/customers/export`, hormati `?q=`, UTF-8 BOM).

#### 3.6 Scanner Tiket (QR Validation)

- [x] **WIRED → DONE** — UI mode manual + camera (Alpine.js, `views/admin/scanner.php`), POST ke `/api/v1/tickets/validate`.
- [x] **DONE** — `ApiController::validateTicket()` tested e2e (valid → used → already_used → invalid). Tenant-scoped (tiket tenant lain = invalid). Scan ulang dicatat sebagai `already_used` di `ticket_scans`.
- [x] **DONE** — Camera scanner pakai `html5-qrcode` (CDN). Ekstrak token dari QR (URL `/t/{token}` atau token mentah), debounce anti-double-submit, toast hasil, riwayat scan live.
- [ ] **TODO** — Offline grace (cache scanned tokens di IndexedDB) — PRD mention.

#### 3.7 Reports

- [x] **DONE** — Sales by event aggregate (`/admin/reports` → `views/admin/reports.php`).
- [x] **DONE** — Filter periode + breakdown harian (`?from=&to=`, basis tanggal lunas/`updated_at`).
- [x] **DONE** — Attendance report (count tiket `used` vs terjual per event + % kehadiran, KPI global).
- [x] **DONE** — Export CSV (`/admin/reports/export`, hormati filter, kolom attendance %).

#### 3.8 Promotions / Coupons

- [x] **DONE** — CRUD page `/admin/promotions`: list dengan badge status (Aktif/Habis/Kedaluwarsa/Akan datang), create/edit form (code unik per tenant, type percentage/fixed, value, usage_limit, valid_from/to, applicable_event_ids), delete.
- [x] **DONE** — Integrasi di checkout: `evaluatePromo` tenant-scoped & event-aware, dipakai di order. Promo divalidasi server-side di `createOrder`, diskon dihitung dari harga DB, `used_count` di-increment. Tested dengan `PROMO10`.

#### 3.9 Settings (Pengaturan)

- [x] **DONE** — `AdminController::settingsSave()` + `POST /admin/settings` (3 section: `branding`, `payment`, `notifications`). Branding update `tenants.name` + `tenants.branding.{primary_color,email_from,reply_to}`. Payment + notifications nyimpen ke `tenants.settings.{payment,notifications}`. Field password masked (`••••••••••`) tidak di-overwrite kalau user tidak ganti.
- [x] **DONE** — Judul situs (suffix tab browser) & teks footer editable dari Settings → Branding (`tenants.branding.{site_title,footer_text}`). Dibaca via `Branding::siteTitle()` / `Branding::footerText()` di layout admin & publik. Kosongkan untuk reset ke default "Visi".
- [ ] **TODO** — Upload logo (perlu storage; bisa filesystem dulu).

#### 3.10 Ticket Categories (Tiket & Harga) — BARU di PR #5

- [x] **DONE** — `/admin/ticket-categories`: list inline-edit (form per-row via HTML5 `form=""` attribute), create modal, delete (guard: tolak hapus jika ada `seats.category_id = ?`).
- [x] **DONE** — Sidebar entry baru di group **Setup** dengan icon `cil-tag`.
- [ ] **TODO** — Inline CRUD dari dalam event editor (currently link out ke `/admin/ticket-categories`).

### 4. Customer Flow (Public)

#### 4.0 Akun Customer (BARU)

- [x] **DONE** — Registrasi & login customer per-tenant (`src/Controllers/CustomerController.php`). Route: `/{slug}/masuk`, `/{slug}/daftar`, `/{slug}/keluar`, `/{slug}/akun`. Pakai `users.role='customer'` + `tenant_id`. Session terpisah dari admin (`customer_id` vs `user_id`) supaya tidak bentrok.
- [x] **DONE** — Auto-link order guest lama: saat daftar/masuk, semua `orders` dengan `user_id IS NULL` + `customer_email` sama otomatis di-tautkan ke akun. Mengurangi data guest terpisah.
- [x] **DONE** — `createOrder` set `orders.user_id` bila customer login (tenant cocok). Guest tetap jalan (user_id NULL).
- [x] **DONE** — Halaman "Akun Saya" (`views/public/account.php`): histori pesanan + status badge + link e-ticket (paid) / lanjut bayar (pending).
- [x] **DONE** — Checkout prefill nama/email/telepon untuk customer login + nudge "Masuk" untuk guest (`views/public/checkout.php`).
- [x] **DONE** — Header publik: tombol Masuk/Daftar (guest) atau nama akun + Keluar (login) (`views/layouts/main.php`).
- [x] **DONE** — Admin login dibatasi role admin/staff/system_admin saja (`AuthController::login`), customer tidak bisa masuk ke panel admin.
- [x] **DONE** — Reset password (lupa password) via email. `/{slug}/lupa-password` + `/{slug}/reset-password/{token}`. Token di-hash (SHA-256), single-use, expiry 1 jam, anti-enumeration. Email via Mailer SMTP. Migration 004 (`password_resets`).
- [x] **DONE** — Edit profil customer (nama/telepon/password) di halaman akun (`POST /{slug}/akun/profil`, ganti password verifikasi password lama).

#### 4.1 Browse

- [x] **DONE** — Homepage daftar tenant (`/`), tenant home daftar event (`/{slug}`), event detail + seat map (`/{slug}/events/{id}`).
- [x] **DONE** — Seat map interaktif (klik seat, pilih kategori, harga muncul). View: `views/public/event.php`.
- [x] **DONE** — Tampilkan promo aktif di event page (badge kode + diskon, seat-map & GA, event-aware).

#### 4.2 Checkout & Payment

- [~] **WIRED → PARTIAL** — Form checkout (`/{slug}/events/{id}/checkout`) ambil seats dari query, POST ke `/api/v1/orders`. Lihat `views/public/checkout.php`.
- [~] **WIRED** — `ApiController::createOrder()` simpan order + customer_name/email/phone + items (cek implementasi lengkap di `src/Controllers/ApiController.php:139`).
- [x] **DONE** — `ApiController::createPaymentIntent()` + `createOrder` kini integrasi **Pakasir** (`src/Pakasir.php`). Order pending → redirect ke halaman bayar Pakasir (QRIS/VA). Kredensial via Settings per-tenant atau `.env` (`PAKASIR_SLUG`/`PAKASIR_API_KEY`).
- [x] **DONE** — Confirmation page (`/{slug}/events/{id}/confirmation`) tampil order_code, status Lunas, dan e-ticket + QR per tiket.
- [ ] **TODO** — Payment retry / cancel flow.

#### 4.3 Webhook & Order Finalization

- [x] **DONE** — `POST /webhooks/payment` handle payload **Pakasir** (`{amount, order_id, project, status, completed_at}`), verifikasi via Transaction Detail API (sumber kebenaran), lalu finalize. Sandbox: `/api/v1/payments/simulate`.
- [ ] **TODO** — Verify webhook signature per provider (PRD requirement). Pakasir tidak pakai signature → verifikasi via detail API + cek nominal/order.
- [x] **DONE** — Idempotency: finalize hanya jalan kalau `order.status != 'paid'` (re-deliver webhook aman). Semua webhook dicatat di `webhook_logs` + `verification_result`.
- [x] **DONE** — Atomic finalize: hold → ticket (transaction), set seats `sold` / GA held→sold, generate QR token. (Bugfix: dulu finalize tidak jalan karena salah resolve order_code vs id.)

#### 4.4 E-Ticket

- [x] **DONE** — Route `/t/{token}` (`PublicController::eticket`).
- [x] **DONE** — Generate QR image server-side via `endroid/qr-code` (composer). `src/Qr.php` → PNG data URI di e-ticket + endpoint `/t/{token}/qr.png`. Fallback ke QR client-side (qrcodejs) kalau `vendor/` belum di-install. **Catatan:** jalankan `composer install` setelah clone.
- [ ] **TODO** — Wallet pass / add-to-Apple-Wallet link (PRD optional).
- [x] **DONE** — Print-friendly: tombol "Cetak / PDF" panggil `window.print()` (QR server-side ikut ter-render tanpa JS).

### 5. Notifikasi

- [x] **DONE** — Email transaksional (order paid, e-ticket) via SMTP (PHPMailer, Gmail App Password). Config per-tenant di Settings (`mail_*`) atau `.env`. Kirim otomatis saat order lunas (`Notifier::sendOrderPaid`). E-ticket HTML + QR inline. *Perlu isi kredensial SMTP untuk aktif.*
- [x] **DONE** — WhatsApp via Fonnte (`src/Fonnte.php`, `FONNTE_TOKEN`). Kirim e-ticket + link saat order lunas. Token per-tenant di Settings atau `.env`. *Perlu isi token Fonnte untuk aktif.*
- [ ] **TODO** — Push (FCM) — PRD optional.
- [x] **DONE** — Tabel `notifications_outbox` (logging + status pending/sent/failed + last_error, idempotensi per channel). Migration 003.
- [~] **WIRED** — Retry notif `failed`: manual via tombol "Kirim ulang" di `/admin/logs` (`Notifier::retry`). Cron worker otomatis bisa ditambah nanti.

### 6. Sistem Promosi

- [~] **WIRED** — `ApiController::evaluatePromo()` ada (`src/Controllers/ApiController.php`), kini tenant-scoped & event-aware, dipakai juga oleh order.
- [ ] **TODO** — Admin UI promo (#3.8 di atas).
- [x] **DONE** — Apply promo di `createOrder` (potong `total_amount_cents` dari harga DB).
- [x] **DONE** — Track `usage_count` per `promotions.id` (increment saat order dibuat dengan diskon).

### 7. Quotas & Inventory

- [x] **DONE** — Schema `ticket_categories.quota`, `seats.status`.
- [x] **DONE** — Enforce quota saat seat hold + order create (lock baris `FOR UPDATE`, cek status available, set `blocked` saat order). Self-healing: order pending basi otomatis dilepas + di-cancel saat ada order baru (TTL `seat_hold_ttl`).
- [x] **DONE** — General Admission (event tanpa kursi: konser stadion, pacuan kuda, festival). Tabel `event_inventory(event_id, category_id, quota, sold, held)` (migration `002_event_inventory.sql`). Anti-oversell via `SELECT ... FOR UPDATE` di `createOrder` (cek `quota - sold - held >= qty`, increment `held`). `finalizeOrder` pindah held→sold; stale-pending & refund melepas held/sold. Admin set kuota per kategori di event editor (tab Tiket, muncul saat tipe = GA). Public page pakai stepper jumlah + "Tersisa N tiket".
- [ ] **TODO** — Tampilkan "Tersisa N tiket" di event page seat-map (GA sudah; seat-map belum).

---

## Phase 2 — Reliability & Observability (post-MVP)

- [ ] **TODO** — Webhook signature verification per provider (Midtrans, Xendit, DOKU).
- [ ] **TODO** — Rate limiting (per IP & per tenant) — PRD non-functional.
- [x] **DONE** — Audit log viewer (`/admin/logs`) — tab Notifikasi (`notifications_outbox`), Webhook (`webhook_logs`), Scan Tiket (`ticket_scans`). + retry notif gagal.
- [x] **DONE** — Retry worker untuk notif `failed` — manual via tombol "Kirim ulang" di `/admin/logs` (`Notifier::retry`). Cron otomatis masih bisa ditambah nanti.
- [ ] **TODO** — Observability: structured logs, error tracking (Sentry?), uptime check.
- [ ] **TODO** — Backup MySQL otomatis (ops).
- [ ] **TODO** — Tests: unit (Models, Services), integration (HTTP), E2E (Playwright).
- [ ] **TODO** — Performance: cache event seat map (sub-second seat ops PRD requirement), seat hold dengan Redis (sekarang plain MySQL).
- [ ] **TODO** — Soft-delete tenants/events/users — kolom `deleted_at` ada di `tenants`, perlu ditambah ke tabel lain.

---

## Phase 3 — Multi-tenant SaaS (jika nanti diaktifkan)

- [ ] **TODO** — Aktifkan kembali `POST /onboarding` (sekarang redirect ke `/login`).
- [ ] **TODO** — Role `system_admin` + super-admin panel `/sysadmin/...` (list tenant, suspend, billing).
- [ ] **TODO** — Pay-as-you-go usage tracking + billing (PRD section 1).
- [ ] **TODO** — Per-tenant subdomain / custom domain mapping.
- [ ] **TODO** — Tenant onboarding wizard (no-code).

---

## Catatan PRD vs implementasi

Mismatch yang perlu keputusan:

1. **Stack berbeda dari PRD.** PRD section 2 sebut "Django backend + Next.js frontend". Implementasi nyata = PHP murni server-rendered. Saya pakai yang nyata (PHP) karena codebase sudah jalan; PRD section 2 sebaiknya di-update atau diberi note "Phase 1 sementara di PHP, migrasi nanti jika perlu".
2. **Payment provider.** PRD sebut Midtrans / Xendit / DOKU. **Keputusan: pakai Pakasir** (QRIS + Virtual Account, https://pakasir.com). Sudah terintegrasi (`src/Pakasir.php`). Tinggal isi `PAKASIR_SLUG` + `PAKASIR_API_KEY` di Settings/`.env` dan set Webhook URL proyek Pakasir ke `/webhooks/payment`.
3. **Notification provider.** PRD sebut SendGrid + Twilio. Email pakai **SMTP (Gmail App Password)** — gratis, tanpa setup DNS, cukup untuk skala project. WhatsApp diganti ke **Fonnte** (lebih relevan & murah untuk pasar Indonesia). Sama — perlu keys.
4. **QR generation.** PRD section 5 sebut external API `api.qrserver.com` atau server-side library. Saat ini belum di-generate. Rekomendasi: server-side lewat `endroid/qr-code` (composer) untuk avoid external dependency.

---

## Cara update file ini

Setiap menutup task: ubah `[ ] **TODO**` → `[x] **DONE**` (atau `WIRED`/`STUB` sesuai status). Commit di branch yang sama dengan implementasinya, msg `docs(roadmap): mark <task>`.
