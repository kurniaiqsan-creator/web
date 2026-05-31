# Visi — Roadmap & Task List

> **Tujuan dokumen ini:** snapshot status implementasi vs PRD. Setiap task punya status, file utama, dan acceptance criteria singkat. AI / developer lain bisa lanjutin tanpa rombak — tinggal cari task `STATUS: TODO` berikutnya.

PRD sumber: `+PRD.txt` (lihat lampiran sesi atau request ulang).
Strategi MVP: **Path A — Single-tenant** (tenant tetap `acoustic-nights`, drop public signup). Schema tetap multi-tenant ready.

Stack saat ini: PHP 8.1 (no framework, custom MVC), MariaDB 10.6, CoreUI Bootstrap 5.4.1 (CDN). Tidak ada build step.

---

## Legenda status

- `DONE` — sudah jadi & ter-smoke-test.
- `WIRED` — fungsi/route ada, UI ada, tapi belum end-to-end (mis. UI mock, backend belum nyimpen).
- `STUB` — placeholder UI saja, belum ada logic backend.
- `TODO` — belum ada.
- `BLOCKED` — perlu kredensial / keputusan eksternal (PG, SendGrid, Twilio, dll.).

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
- [ ] **TODO** — Audit log viewer di admin (table `webhook_logs` ada; perlu page `/admin/logs`).

### 2. UI / Theming

- [x] **DONE** — CoreUI Bootstrap 5.4.1 (CDN). 1 sistem styling. Tailwind dibuang. (`views/layouts/*`)
- [x] **DONE** — Dark/Light/Auto toggle dengan localStorage `visi-theme` (`assets/js/color-modes.js`).
- [x] **DONE** — Sidebar admin 3 grup (Operasional / Insight / Setup) + CoreUI `cil-*` icons.
- [x] **DONE** — Visi brand orange (`#f97316` / `#fb923c` dark) override CoreUI primary (`assets/css/visi.css`).
- [x] **DONE** — Seat map theme-aware (CSS vars per `[data-coreui-theme]`).
- [ ] **TODO** — Tenant branding actually applied: ambil `tenants.branding_json.primary_color` → inject ke CSS vars per-tenant (sekarang Visi orange dipakai global; settings page punya color picker tapi tidak nyimpen / apply).
- [ ] **TODO** — Logo tenant di header public & admin (sekarang teks "Visi Admin"). Field `branding.logo_url` ada di schema.

### 3. Admin Panel

#### 3.1 Dashboard

- [x] **DONE** — Stat cards (penjualan hari ini, order pending, tiket terjual, total order) + tabel "Pesanan Terbaru" (`views/admin/dashboard.php`).
- [ ] **TODO** — Chart penjualan 7/30 hari (PRD mention "basic reporting").
- [ ] **TODO** — Filter periode di dashboard.

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
- [ ] **TODO** — Filter status, tanggal, search.

#### 3.5 Customer (BARU di PR #2)

- [x] **DONE** — List customer agregat by `COALESCE(user_id, customer_email)` (`/admin/customers`).
- [x] **DONE** — Detail customer + histori order (`/admin/customers/{id}` atau `/admin/customers/email:{email}`).
- [x] **DONE** — Telepon pakai `COALESCE(u.phone, MAX(o.customer_phone))` (fix commit `05e09c5`).
- [ ] **TODO** — Search di list (input ada, tapi tidak filter — belum di-wire ke backend / client-side).
- [ ] **TODO** — Export CSV daftar customer.

#### 3.6 Scanner Tiket (QR Validation)

- [~] **WIRED** — UI mode manual + camera (Alpine.js, `views/admin/scanner.php`), POST ke `/api/v1/tickets/validate`.
- [~] **WIRED** — `ApiController::validateTicket()` ada — perlu test end-to-end dengan tiket beneran (lihat 4.3).
- [ ] **TODO** — Camera scanner pakai library (mis. `html5-qrcode`); sekarang mode camera kemungkinan placeholder.
- [ ] **TODO** — Offline grace (cache scanned tokens di IndexedDB) — PRD mention.

#### 3.7 Reports

- [x] **DONE** — Sales by event aggregate (`/admin/reports` → `views/admin/reports.php`).
- [ ] **TODO** — Filter periode + breakdown harian.
- [ ] **TODO** — Attendance report (count tiket `used` per event).
- [ ] **TODO** — Export CSV (PRD requirement).

#### 3.8 Promotions / Coupons

- [x] **DONE** — CRUD page `/admin/promotions`: list dengan badge status (Aktif/Habis/Kedaluwarsa/Akan datang), create/edit form (code unik per tenant, type percentage/fixed, value, usage_limit, valid_from/to, applicable_event_ids), delete.
- [ ] **TODO** — Integrasi di checkout: `ApiController::evaluatePromo` sudah ada — perlu test end-to-end dengan kode `PROMO10` yang ter-seed.

#### 3.9 Settings (Pengaturan)

- [x] **DONE** — `AdminController::settingsSave()` + `POST /admin/settings` (3 section: `branding`, `payment`, `notifications`). Branding update `tenants.name` + `tenants.branding.{primary_color,email_from,reply_to}`. Payment + notifications nyimpen ke `tenants.settings.{payment,notifications}`. Field password masked (`••••••••••`) tidak di-overwrite kalau user tidak ganti.
- [ ] **TODO** — Upload logo (perlu storage; bisa filesystem dulu).

#### 3.10 Ticket Categories (Tiket & Harga) — BARU di PR #5

- [x] **DONE** — `/admin/ticket-categories`: list inline-edit (form per-row via HTML5 `form=""` attribute), create modal, delete (guard: tolak hapus jika ada `seats.category_id = ?`).
- [x] **DONE** — Sidebar entry baru di group **Setup** dengan icon `cil-tag`.
- [ ] **TODO** — Inline CRUD dari dalam event editor (currently link out ke `/admin/ticket-categories`).

### 4. Customer Flow (Public)

#### 4.1 Browse

- [x] **DONE** — Homepage daftar tenant (`/`), tenant home daftar event (`/{slug}`), event detail + seat map (`/{slug}/events/{id}`).
- [x] **DONE** — Seat map interaktif (klik seat, pilih kategori, harga muncul). View: `views/public/event.php`.
- [ ] **TODO** — Tampilkan promo aktif di event page.

#### 4.2 Checkout & Payment

- [~] **WIRED → PARTIAL** — Form checkout (`/{slug}/events/{id}/checkout`) ambil seats dari query, POST ke `/api/v1/orders`. Lihat `views/public/checkout.php`.
- [~] **WIRED** — `ApiController::createOrder()` simpan order + customer_name/email/phone + items (cek implementasi lengkap di `src/Controllers/ApiController.php:139`).
- [ ] **TODO** — `ApiController::createPaymentIntent()` — stub saat ini, belum panggil Midtrans/Xendit/DOKU API. **BLOCKED** sampai user kasih PG sandbox keys.
- [ ] **TODO** — Confirmation page (`/{slug}/events/{id}/confirmation`) sekarang masih placeholder — perlu show order_code, status, link e-ticket.
- [ ] **TODO** — Payment retry / cancel flow.

#### 4.3 Webhook & Order Finalization

- [~] **WIRED** — `POST /webhooks/payment` ada (`ApiController::paymentWebhook`, line 346). **BLOCKED** untuk e2e sampai PG terintegrasi.
- [ ] **TODO** — Verify webhook signature per provider (PRD requirement: "All webhooks must verify provider signature").
- [ ] **TODO** — Idempotency check (gunakan `webhook_logs` + dedup by `provider_payment_id`).
- [ ] **TODO** — Atomic finalize: hold → ticket (transaction), set seats `sold`, generate QR token, dispatch notifications.

#### 4.4 E-Ticket

- [x] **DONE** — Route `/t/{token}` (`PublicController::eticket`).
- [ ] **TODO** — Generate QR image server-side (perlu library mis. `endroid/qr-code` via composer, atau pakai pattern PRD `api.qrserver.com`).
- [ ] **TODO** — Wallet pass / add-to-Apple-Wallet link (PRD optional).
- [ ] **TODO** — Print-friendly PDF / email-friendly HTML.

### 5. Notifikasi

- [ ] **TODO** — Email transaksional (order paid, e-ticket). **BLOCKED** sampai SendGrid API key tersedia. PRD spec di section 5.
- [ ] **TODO** — SMS/WhatsApp via Twilio. **BLOCKED** sampai Twilio creds.
- [ ] **TODO** — Push (FCM) — PRD optional.
- [ ] **TODO** — Tabel `notifications_outbox` (queue ringan, retry, logging). Belum ada di schema.

### 6. Sistem Promosi

- [~] **WIRED** — `ApiController::evaluatePromo()` ada (`src/Controllers/ApiController.php:215`), belum di-test integrasi dengan checkout flow.
- [ ] **TODO** — Admin UI promo (#3.8 di atas).
- [ ] **TODO** — Apply promo di `createOrder` (potong `total_amount_cents`).
- [ ] **TODO** — Track `usage_count` per `promotions.id`.

### 7. Quotas & Inventory

- [x] **DONE** — Schema `ticket_categories.quota`, `seats.status`.
- [ ] **TODO** — Enforce quota saat seat hold + order create (lock + decrement atomically).
- [ ] **TODO** — Tampilkan "Tersisa N tiket" di event page.

---

## Phase 2 — Reliability & Observability (post-MVP)

- [ ] **TODO** — Webhook signature verification per provider (Midtrans, Xendit, DOKU).
- [ ] **TODO** — Rate limiting (per IP & per tenant) — PRD non-functional.
- [ ] **TODO** — Audit log viewer (`/admin/logs`) — table `webhook_logs`, `ticket_scans` existing.
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
2. **Payment provider.** PRD sebut Midtrans / Xendit / DOKU. Belum ada keys → semua flow PG di-stub. Action: minta sandbox keys + pilih provider default untuk Phase 1.
3. **Notification provider.** PRD sebut SendGrid + Twilio. Sama — perlu keys.
4. **QR generation.** PRD section 5 sebut external API `api.qrserver.com` atau server-side library. Saat ini belum di-generate. Rekomendasi: server-side lewat `endroid/qr-code` (composer) untuk avoid external dependency.

---

## Cara update file ini

Setiap menutup task: ubah `[ ] **TODO**` → `[x] **DONE**` (atau `WIRED`/`STUB` sesuai status). Commit di branch yang sama dengan implementasinya, msg `docs(roadmap): mark <task>`.
