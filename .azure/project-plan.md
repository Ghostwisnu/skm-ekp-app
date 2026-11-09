# Project Plan

**Status**: Planning
**Created**: 2026-11-03
**Mode**: AUGMENT

---

## 1. Project Overview

**Goal**: Aplikasi internal pencatatan perjalanan (trip) truk, biaya harian, invoice per client, dan laporan kinerja per plat nomor, dibangun di atas proyek Laravel + Inertia React yang sudah ada. Antarmuka berbahasa Indonesia, mata uang Rupiah (Rp). The project is designed so that every module is independently testable.

**App Type**: Full-stack SSR (Laravel + Inertia React, satu aplikasi)

**API Login**: Yes

**Mode**: AUGMENT

**Deployment Plan**: No deployment plan found. Fokus pengembangan lokal; tidak ada provisioning Azure.

**Roles**: `admin` (semua akses + kelola user & pengaturan PPN), `operator` (input trip, surat jalan, biaya harian), `keuangan` (harga, invoice, laporan, ekspor).

**Asumsi (default sederhana, dapat diubah saat review)**:

1. Data client: `nama`, `alamat`, `NPWP` (opsional), `kontak/telepon` (opsional).
2. Nomor surat jalan: field teks pada trip (satu surat jalan per trip), unik, diisi manual.
3. Satu trip = satu plat + nama sopir (teks bebas, tanpa master sopir) + satu client + tanggal + banyak baris barang (`trip_items`: jenis barang, tonase kg). Harga per kg diisi manual saat membuat invoice.
4. Total biaya harian = uang jalan 1 + uang bongkar + bonus tepat waktu + bonus tambahan + pinjaman + uang jalan 2 + simpanan (penjumlahan murni, tanpa pengurangan). Biaya dicatat per trip (per hari per plat).
5. Invoice: dibuat per client untuk satu hari, satu bulan, atau rentang tanggal; mengambil semua trip client pada periode yang belum ter-invoice. Nomor invoice diisi manual dan unik. Subtotal = tonase x harga.
6. PPN: persentase diatur manual per invoice (default 11%, bisa 0%); default dapat diubah admin di Pengaturan. Total = subtotal + PPN.
7. Uang disimpan sebagai integer Rupiah (tanpa desimal); tonase dalam kg (decimal 12,2).
8. Margin per plat = nilai invoice (sebelum PPN) - total biaya harian pada periode.
9. Verifikasi email dinonaktifkan; registrasi publik dimatikan, user dibuat oleh admin.
10. Ekspor PDF (cetak invoice & laporan) dan Excel (rekap invoice & laporan kinerja).

---

## 2. Backend — Laravel

| Component           | Technology           |
| ------------------- | -------------------- |
| **Language**        | PHP 8.5              |
| **Runtime**         | PHP 8.5 (Laravel 13) |
| **Package Manager** | composer             |
| **Test Runner**     | Pest 5               |
| **Mocking Library** | Mockery              |
| **Test Command**    | php artisan test     |
| **Orchestration**   | docker-compose       |

Paket tambahan yang diusulkan: `barryvdh/laravel-dompdf` (PDF), `maatwebsite/excel` (Excel). Autentikasi memakai Laravel Fortify yang sudah ada (verifikasi email & registrasi dinonaktifkan). Otorisasi lewat kolom `role` pada `users` + Policy/middleware role.

---

## 3. Frontend — Inertia React

| Component           | Technology                                                |
| ------------------- | --------------------------------------------------------- |
| **Language**        | TypeScript                                                |
| **Framework**       | React 19 + Inertia 3 + Vite (Tailwind 4, Radix UI/shadcn) |
| **Package Manager** | npm                                                       |
| **Test Runner**     | vitest                                                    |
| **Mocking Library** | vi.mock                                                   |
| **Test Command**    | npm test                                                  |

---

## 4. Services Required

| Azure Service              | Role in App            | Environment Variable                                              | Default Value (Local)                         | Classification |
| -------------------------- | ---------------------- | ----------------------------------------------------------------- | --------------------------------------------- | -------------- |
| MySQL (lokal, bukan Azure) | Penyimpanan data utama | DB_CONNECTION / DB_HOST / DB_DATABASE / DB_USERNAME / DB_PASSWORD | mysql / 127.0.0.1 / skm_ekp / root / (kosong) | Essential      |

Tidak ada layanan Azure yang diprovisikan pada tahap ini.

---

## 5. Prerequisites

### Run

| Tool         | Service(s) | Installed | Version |
| ------------ | ---------- | --------- | ------- |
| PHP          | Backend    | ✅        | 8.5.10  |
| Composer     | Backend    | ✅        | 2.10.3  |
| Node.js      | Frontend   | ✅        | 24.22.0 |
| npm          | Frontend   | ❓        | —       |
| MySQL server | Backend    | ❓        | —       |

### Debug

| Tool           | Service(s) | Installed | Version |
| -------------- | ---------- | --------- | ------- |
| Docker         | Backend    | ❓        | —       |
| Docker Compose | Backend    | ❓        | —       |

---

## 6. Design System & UI

**Component Library**: shadcn/ui (Radix UI + Tailwind 4), komponen yang sudah ada di proyek
**Style Direction**: Konsol operasional padat data dan mudah dipindai; tabel dominan, sudut membulat 6px, angka Rupiah rata kanan.
**Typography**: Inter, system-ui

### Color Palette

| Token     | Hex       | Usage                                             |
| --------- | --------- | ------------------------------------------------- |
| `primary` | `#1d4ed8` | Tombol utama, tautan, menu aktif                  |
| `accent`  | `#f59e0b` | Penanda invoice draft/belum lunas, sorotan margin |
| `surface` | `#f8fafc` | Latar halaman dan kartu                           |
| `text`    | `#0f172a` | Teks utama, angka tabel                           |
| `muted`   | `#64748b` | Keterangan, label filter                          |
| `border`  | `#e2e8f0` | Garis tabel, border input                         |

### Pages

| Page                  | Route               | Purpose                                               | Layout                                                    |
| --------------------- | ------------------- | ----------------------------------------------------- | --------------------------------------------------------- |
| Dashboard             | `/dashboard`        | Ringkasan trip, tonase, biaya, invoice bulan berjalan | `header, sidebar, grid, table`                            |
| Trip                  | `/trips`            | Daftar & filter trip (tanggal, plat, client)          | `header, sidebar, actions, table`                         |
| Form Trip             | `/trips/create`     | Input trip, sopir, surat jalan, banyak barang         | `header, sidebar, form`                                   |
| Biaya Harian          | `/daily-costs`      | Input & rekap komponen biaya per trip/plat            | `header, sidebar, actions, table`                         |
| Client                | `/clients`          | Master client                                         | `header, sidebar, actions, table`                         |
| Kendaraan (Plat)      | `/vehicles`         | Master plat nomor                                     | `header, sidebar, actions, table`                         |
| Invoice               | `/invoices`         | Daftar invoice per client                             | `header, sidebar, actions, table`                         |
| Buat Invoice          | `/invoices/create`  | Pilih client + periode, isi harga, nomor invoice, PPN | `header, sidebar, form, table`                            |
| Detail Invoice        | `/invoices/{id}`    | Rincian, cetak PDF, ekspor Excel                      | `header, sidebar, two-column(main+card-list), action-bar` |
| Laporan Kinerja Plat  | `/reports/vehicles` | Trip, tonase, nilai invoice, biaya, margin per plat   | `header, sidebar, form, table, actions`                   |
| Pengguna & Pengaturan | `/admin/users`      | Kelola user/role dan PPN default (admin)              | `header, sidebar, table, form`                            |

### Sample Content

Trip — `/trips`:

| Tanggal    | Plat       | Sopir  | Client          | No. Surat Jalan | Tonase (kg) |
| ---------- | ---------- | ------ | --------------- | --------------- | ----------- |
| 2026-11-01 | B 9123 KXA | Slamet | PT Sinar Makmur | SJ-2611-0012    | 8.500       |
| 2026-11-01 | D 8841 AB  | Hendra | CV Karya Abadi  | SJ-2611-0013    | 12.250      |
| 2026-11-02 | B 9123 KXA | Slamet | PT Sinar Makmur | SJ-2611-0021    | 9.100       |

Biaya Harian — `/daily-costs`:

| Tanggal    | Plat       | Uang Jalan 1 | Uang Bongkar | Bonus Tepat Waktu | Bonus Tambahan | Pinjaman   | Uang Jalan 2 | Simpanan  | Total        |
| ---------- | ---------- | ------------ | ------------ | ----------------- | -------------- | ---------- | ------------ | --------- | ------------ |
| 2026-11-01 | B 9123 KXA | Rp 500.000   | Rp 150.000   | Rp 50.000         | Rp 0           | Rp 100.000 | Rp 300.000   | Rp 50.000 | Rp 1.150.000 |
| 2026-11-01 | D 8841 AB  | Rp 600.000   | Rp 200.000   | Rp 0              | Rp 25.000      | Rp 0       | Rp 0         | Rp 50.000 | Rp 875.000   |

Invoice — `/invoices`:

| No. Invoice     | Client          | Periode       | Subtotal      | PPN | Total         | Status |
| --------------- | --------------- | ------------- | ------------- | --- | ------------- | ------ |
| INV/2026/11/001 | PT Sinar Makmur | 1–30 Nov 2026 | Rp 18.500.000 | 11% | Rp 20.535.000 | Draft  |
| INV/2026/10/014 | CV Karya Abadi  | 1–31 Okt 2026 | Rp 12.250.000 | 11% | Rp 13.597.500 | Final  |

Laporan Kinerja Plat — `/reports/vehicles`:

| Plat       | Trip | Tonase (kg) | Nilai Invoice | Biaya         | Margin        |
| ---------- | ---- | ----------- | ------------- | ------------- | ------------- |
| B 9123 KXA | 22   | 190.400     | Rp 28.560.000 | Rp 19.800.000 | Rp 8.760.000  |
| D 8841 AB  | 18   | 211.000     | Rp 31.650.000 | Rp 16.200.000 | Rp 15.450.000 |

---

## 7. Project Structure

```
app/
  Models/            User(role), Client, Vehicle, Trip, TripItem, DailyCost, Invoice, InvoiceLine, Setting
  Enums/             Role
  Http/Controllers/  Trip, DailyCost, Client, Vehicle, Invoice, Report, User, Setting
  Http/Middleware/   EnsureRole
  Http/Requests/     Form Request per resource
  Policies/          Per model
  Services/          InvoiceBuilder, VehiclePerformanceReport
  Exports/           InvoiceExport, VehiclePerformanceExport
database/migrations/ users.role, clients, vehicles, trips, trip_items, daily_costs, invoices, invoice_lines, settings
database/factories/, database/seeders/ (user admin/operator/keuangan untuk dev)
resources/js/pages/  dashboard, trips, daily-costs, clients, vehicles, invoices, reports, admin
resources/views/pdf/ invoice.blade.php, vehicle-report.blade.php
tests/Feature/, tests/Unit/
```

---

## 8. Route Definitions

| #   | Method       | Path                                               | Description                                                  | Request Body                                                                    | Response Body      | Status Codes       |
| --- | ------------ | -------------------------------------------------- | ------------------------------------------------------------ | ------------------------------------------------------------------------------- | ------------------ | ------------------ |
| 1   | GET          | `/up`                                              | Health check                                                 | —                                                                               | status             | 200                |
| 2   | GET          | `/dashboard`                                       | Dashboard (semua role)                                       | —                                                                               | Inertia page       | 200                |
| 3   | GET/POST     | `/trips`, `/trips/create`                          | Daftar & buat trip (admin, operator)                         | trip + items[]                                                                  | Inertia / redirect | 200, 302, 422      |
| 4   | PUT/DELETE   | `/trips/{id}`                                      | Ubah/hapus trip                                              | trip + items[]                                                                  | redirect           | 302, 403, 422      |
| 5   | GET/POST/PUT | `/daily-costs`                                     | Rekap & input 7 komponen biaya (admin, operator)             | komponen biaya                                                                  | Inertia / redirect | 200, 302, 422      |
| 6   | CRUD         | `/clients`, `/vehicles`                            | Master client & plat                                         | field master                                                                    | Inertia / redirect | 200, 302, 422      |
| 7   | GET/POST     | `/invoices`, `/invoices/create`                    | Daftar & buat invoice per client + periode (admin, keuangan) | client_id, tanggal_awal, tanggal_akhir, no_invoice, ppn_persen, harga per baris | redirect           | 200, 302, 422      |
| 8   | GET          | `/invoices/{id}`                                   | Detail invoice                                               | —                                                                               | Inertia page       | 200, 403, 404      |
| 9   | GET          | `/invoices/{id}/pdf`, `/invoices/{id}/excel`       | Cetak PDF / ekspor Excel                                     | —                                                                               | file               | 200, 403           |
| 10  | GET          | `/reports/vehicles`                                | Laporan kinerja per plat; filter tanggal, plat, client       | query                                                                           | Inertia page       | 200                |
| 11  | GET          | `/reports/vehicles/pdf`, `/reports/vehicles/excel` | Ekspor laporan                                               | query                                                                           | file               | 200                |
| 12  | CRUD         | `/admin/users`                                     | Kelola user & role (admin)                                   | name, email, role, password                                                     | redirect           | 200, 302, 403, 422 |
| 13  | GET/PUT      | `/admin/settings`                                  | PPN default (admin)                                          | ppn_default                                                                     | redirect           | 200, 302, 422      |

---

## 9. Next Steps

1. Run **azure-project-scaffold** to execute this plan
2. Run **azure-project-integrate** to wire the frontend to live data, smoke-test the backend, and create the migrations
3. Run **azure-debug-plan** → **azure-debug-generate** for Docker emulators and VS Code debugging
4. Run the **azure-deploy** agent when ready (di luar cakupan saat ini)
