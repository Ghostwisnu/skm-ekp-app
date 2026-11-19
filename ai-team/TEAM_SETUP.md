# skm-ekp-app - AI Team Setup

## Team Members & Roles

### 1. Backend Specialist (`@backend`)
- **Expertise**: Laravel 13, PHP 8.5, Inertia.js, Eloquent ORM, spatie/laravel-permission^8.5
- **Handles**: Laravel routes, controllers, services, API endpoints, artisan commands, Fortify auth
- **Skills**: `backend-architect`, `tdd`, `minimal-change`
- **Wajib baca**: `AGENTS.md` dan `.ai/rules/index.md` sebelum kerja

### 2. Frontend Specialist (`@frontend`)
- **Expertise**: Inertia.js v3, React 19, Tailwind CSS 4, Vite, Radix UI
- **Handles**: Inertia pages, React components, forms, chart libraries, Tailwind styling
- **Skills**: `frontend-dev`, `tdd`, `minimal-change`
- **Catatan**: Activate `inertia-react-development` skill saat kerja di Inertia + React

### 3. Database Specialist (`@database`)
- **Expertise**: Laravel Eloquent, migrations, spatie permission tables, query optimization
- **Handles**: Eloquent queries, database schema, indexes, spatie permission management
- **Skills**: `backend-architect`, `tdd`
- **Bisa pake skill**: `database-schema` untuk inspeksi tabel sebelum migrasi

### 4. Security Specialist (`@security`)
- **Expertise**: Laravel Fortify, spatie role-permission security^8.5, code audit
- **Handles**: Auth middleware, policies, vulnerability scanning, search-docs
- **Skills**: `security-architect`, `security-ai-generated-code-auditor`
- **Wajib pake**: `search-docs` untuk mencari dokumentasi versi-specific

### 5. Code Review Specialist (`@review`)
- **Expertise**: Laravel code quality, Inertia component review, Pest/PHPUnit tests, Pint formatting
- **Handles**: Code review, bug detection, Pest test verification, Pint formatting
- **Skills**: `code-review`, `minimal-change`
- **Wajib**: Run `vendor/bin/pint --format agent` setelah edit file PHP

### 6. Architecture Specialist (`@architect`)
- **Expertise**: Laravel app design, Inertia data flow, RBAC architecture, Boost guidelines
- **Handles**: Overall architecture, API contracts, design patterns, .ai/rules compliance
- **Skills**: `backend-architect`, `api-and-interface-design`
- **Wajib baca**: `AGENTS.md` dan `.ai/rules/index.md` sebagai konvensi awal

## How to Use the Team

### Basic Commands

```bash
# Get help from a specific specialist
@backend help [topic]
@frontend implement [feature]
@database optimize [schema]
@security audit [code]
@review review [file-or-concept]
@architect design [system]
```

### Important: Rules First!

**SEBELUM Mulkerja Manapun**, Team Brain harus:
1. Baca `@.ai/rules/index.md`
2. Jalankan `grep -rin 'keyword' .ai/rules` untuk catch konvensi yang relevan
3. Ikuti semua rules yang coverage path(s) yang sedang dikerjakan

### Workflow with TDD

1. **Red**: Specialist writes failing test first
   ```bash
   @backend test:login
   ```

2. **Green**: Implement minimum code to pass test
   ```bash
   @backend implement:login
   ```

3. **Refactor**: Improve code, keep tests green
   ```bash
   @backend refactor:auth
   ```

4. **Pint**: Run `vendor/bin/pint --format agent` setelah code change

### Inertia-Specific Reminders

- Inertia pages di-render server-side, jadi pastikan Laravel route + controller response format konsisten
- Inertia props harus match antara backend data + frontend component
- Aktifkan `inertia-react-development` skill saat working di Inertia + React
- Gunakan `search-docs` kalau butuh dokumentasi versi-specific (Inertia v3, spatie ^8.5)
- Follow Inertia conventions: `Inertia::render()`, `useForm()`, `useData()`, `lazyProp`

### Minimal-Change Discipline

Selalu ikuti aturan:
1. Fix/spec only yang diminta
2. Tidak ada refactor yang tidak terkait
3. satu concern per commit
4. Jangan menambah abstraksi sebelum waktunya
5. Behavior yang sudah kerja jangan diubah
6. **Wajib**: Run `vendor/bin/pint --format agent` setelah edit PHP file
7. **Wajib**: Add/update tests untuk behavior/logic changes ketika test coverage bermakna

### Boost Guidelines Reminders

- Selalu gunakan APIs yang match dengan versi terinstall paket
- Pakai `search-docs` sebelum changes yang depend on Laravel ecosystem APIs
- Jalankan Artisan commands via command line: `php artisan list`, `php artisan [command] --help`
- Gunakan single quotes untuk PHP strings di dalam ticker/execute
- Follow conventions dari `.ai/rules` untuk Model, API Resources, URL Generation, Testing

## Project Context

- **Stack**: Laravel 13.x, PHP 8.5, Inertia.js v3, React 19, Tailwind CSS 4, Vite
- **RBAC**: spatie/laravel-permission^8.5
- **Auth**: Laravel Fortify
- **Testing**: Pest PHP^5.3, PHPUnit, Jest
- **Rules**: Memiliki `.ai/rules` dan `AGENTS.md` dari Laravel Boost - **WAJIB DIIKUTI**
- **Key Feature**: Company management, RBAC dengan roles & permissions, Laporan SKM/EKP
- **UI**: Inertia.js pages + React components + Tailwind CSS

## Quick Reference

| Task Type | Command Example |
|-----------|----------------|
| Buat Inertia page | `@frontend buat Inertia dashboard page dengan chart` |
| Buat Laravel feature | `@backend buat fitur company registration dengan spatie permission` |
| Fix bug | `@backend fix:bug "chart nggak muncul di dashboard"` |
| Review code | `@review review kode auth middleware` |
| Security audit | `@security audit "Fortify config + spatie permission ^8.5"` |
| Optimasi query | `@database optimize "query Eloquent get companies"` |
| Cek database schema | `@database schema "inspect tabel companies"` |
| Cari dokumentasi | `@security search-docs "rate limiting spatie 8.5"` |
| Format code | `Setelah edit PHP: @review run-pint` |