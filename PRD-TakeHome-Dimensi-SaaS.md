# PRD — Mini Project Management SaaS (Take-Home Test Dimensi Software)

---

## 📊 PROGRESS TRACKER
> *Diupdate otomatis oleh pair-programmer. Last update: 2026-07-20*

| # | Milestone | Status | Commit |
|---|-----------|--------|--------|
| 1 | Skema DB (migrations + models + index) | ✅ Selesai | `39d0355` |
| 2 | Auth Sanctum + Global Scope tenant isolation | ✅ Selesai | `f876ddc` |
| 2b | Fix: CompanyScope guard hardening + exception handlers + default role=member | ✅ Selesai | *(belum commit)* |
| 3 | CRUD Project & Task + Policy RBAC + Kelola User | ✅ Selesai | *(belum commit)* |
| 4 | Background Job (SendTaskAssignedNotification) | ✅ Selesai | *(stub, dispatch ada di TaskService)* |
| 5 | Testing (tenant isolation + RBAC + validasi) | ✅ Selesai | *(belum commit)* |
| 6 | Seeder + README | ✅ Selesai | *(belum commit)* |
| 7 | Frontend Blade + Livewire *(opsional)* | 🔲 Belum | — |

### File yang sudah dibuat

```
database/migrations/
  0000_00_00_000000_create_companies_table.php     ✅
  0001_01_01_000000_create_users_table.php          ✅ (+company_id, role=member default)
  2024_01_01_000001_create_projects_table.php       ✅
  2024_01_01_000002_create_tasks_table.php          ✅ (company_id denormalized + 3 index)

app/Models/
  Company.php                                       ✅
  User.php                                          ✅ (+HasApiTokens, isAdmin/isMember)
  Project.php                                       ✅ (BelongsToCompany trait)
  Task.php                                          ✅ (BelongsToCompany trait)
  Scopes/CompanyScope.php                           ✅ (guard berlapis, sumber HANYA auth)

app/Traits/
  BelongsToCompany.php                              ✅ (global scope + auto-fill company_id)
  ApiResponse.php                                   ✅ (successResponse + errorResponse)

app/Policies/
  ProjectPolicy.php                                 ✅ (admin=full CRUD, member=read)
  TaskPolicy.php                                    ✅ (member update: cek assigned_to===id)
  UserPolicy.php                                    ✅ (admin-only)

app/Http/Requests/
  Auth/RegisterRequest.php                          ✅
  Auth/LoginRequest.php                             ✅
  Project/StoreProjectRequest.php                   ✅
  Project/UpdateProjectRequest.php                  ✅ (PATCH via 'sometimes')
  Task/StoreTaskRequest.php                         ✅ (assigned_to: Rule::exists scoped)
  Task/UpdateTaskRequest.php                        ✅ (member=status only, admin=all fields)
  User/StoreUserRequest.php                         ✅ (no role/company_id field)

app/Http/Resources/
  UserResource.php                                  ✅
  ProjectResource.php                               ✅ (whenLoaded → no N+1)
  TaskResource.php                                  ✅ (whenLoaded → no N+1)

app/Services/
  AuthService.php                                   ✅
  ProjectService.php                                ✅ (with() eager load di semua method)
  TaskService.php                                   ✅ (dispatch job saat assigned_to berubah)
  UserService.php                                   ✅ (hardcode role=member, explicit company filter)

app/Jobs/
  SendTaskAssignedNotification.php                  ✅ (ShouldQueue + Log::info)

app/Http/Controllers/Api/V1/
  Auth/AuthController.php                           ✅
  ProjectController.php                             ✅ (tipis, authorize→service→response)
  TaskController.php                                ✅ (+ensureTaskBelongsToProject guard)
  UserController.php                                ✅

routes/api.php                                      ✅ (15 routes, semua sesuai PRD)
bootstrap/app.php                                   ✅ (6 exception handlers → envelope)
```

### Keputusan arsitektur yang sudah diambil

1. **Row-level scoping** via `BelongsToCompany` trait + `CompanyScope` global scope
2. **company_id selalu dari token** — tidak pernah dari request/URL/body, di-enforce di 3 lapis:
   - `CompanyScope::apply()` — setiap query otomatis ter-filter
   - `BelongsToCompany::bootBelongsToCompany()` — auto-fill saat `creating`
   - Service layer — hardcode di `UserService::store()` juga
3. **Thin controller pattern** — semua logic di Service class
4. **UpdateTaskRequest role-based rules** — member hanya bisa kirim `status`, field lain diabaikan di level validasi (tidak sampai ke service)
5. **TaskController::ensureTaskBelongsToProject()** — cegah task hopping antar project dalam company yang sama
6. **N+1 prevention** — semua service method pakai `with()`, resource pakai `whenLoaded()`
7. **assigned_to validation** — `Rule::exists()->where('company_id', ...)` cegah cross-tenant assignment
8. **job dispatch only when assigned_to changes** — `dispatchNotificationIfAssigned()` bandingkan nilai lama vs baru

---

## 0. Ringkasan Soal
Membuat backend **multi-tenant SaaS** untuk mini Project Management (versi kecil Asana/Trello).
- **Penilai fokus ke:** cara berpikir & keputusan arsitektur, BUKAN kelengkapan fitur.
- **Waktu efektif target:** 4-6 jam kerja. Tenggat kirim: 3x24 jam.
- **Prioritas kalau waktu mepet:** sedikit fitur tapi benar + terdokumentasi > banyak fitur setengah jadi.
- **Yang paling berat bobotnya (★★★★★):** tenant isolation harus benar dan **terbukti lewat test**.

Bagian ini yang menentukan lulus/tidaknya kamu — jangan sampai waktu habis di fitur pelengkap sementara isolation test belum jalan.

---

## 1. Keputusan Stack (sudah difinalkan, jangan bimbang lagi di tengah jalan)

| Layer | Pilihan | Alasan |
|---|---|---|
| Framework | **Laravel 11** | Kamu paling kuasai ini, ekosistemnya lengkap (migration, queue, Sanctum, policy/gate) — paling cepat untuk hasil rapi dalam 4-6 jam |
| DB | **MySQL** | Sesuai requirement relational DB, familiar buatmu |
| Auth | **Laravel Sanctum** (token-based) | Simpel untuk REST API, gampang dijelaskan di README |
| Queue | **Laravel Queue** (driver `database`) | Tidak butuh Redis, gampang dinilai penguji tanpa setup tambahan |
| Testing | **PHPUnit / Pest** (bawaan Laravel) | Feature test langsung hit endpoint, paling representatif untuk buktikan tenant isolation |
| RBAC | **Laravel Policy + custom Role enum/column** | Cukup 2 role (admin/member), tidak perlu package spatie/permission — biar sederhana dan gampang dijelaskan |
| Frontend | **Blade + Livewire** (opsional, lihat bagian 2b) | Reuse auth/session & Policy backend, paling cepat dibangun sisa waktu, tidak perlu state management JS terpisah |

**Alasan tulis di README:** stack dipilih karena familiaritas → memaksimalkan waktu untuk kualitas arsitektur & test, bukan belajar tool baru.

---

## 2. Strategi Multi-Tenancy: **Row-Level Scoping**

Ini keputusan arsitektur paling penting di soal ini. Pilih **row-level scoping** (semua tenant satu DB, dibedakan kolom `company_id`), bukan schema-per-tenant atau database-per-tenant.

**Kenapa:**
- Skala kecil (take-home), setup paling cepat, paling gampang di-review oleh penguji.
- Trade-off yang harus disebut di README: row-level scoping lebih rawan human-error (lupa filter `company_id` di satu query = data bocor), makanya wajib **di-enforce di level global, bukan manual per-query**.

**Cara enforce (WAJIB, ini yang dinilai ★★★★★):**
1. Semua tabel tenant-scoped (`projects`, `tasks`) punya kolom `company_id` (foreign key, NOT NULL, indexed).
2. Pakai **Laravel Global Scope** (`BelongsToCompany` trait + `ScopedByCompany` global scope) yang otomatis nambahin `WHERE company_id = ?` ke SETIAP query model tanpa perlu ditulis manual di controller.
3. `company_id` untuk scope ini diambil dari `auth()->user()->company_id` — **tidak pernah dari request/URL/body**.
4. Saat create resource baru, `company_id` di-set otomatis dari user login (model event `creating`), bukan dari input user.
5. Route Model Binding + Policy: kalau resource ID valid tapi beda company → return **403/404**, bukan 200 dengan data kosong.

**Trade-off yang perlu ditulis di README:**
- Row-level scoping = simple & murah, tapi butuh disiplin (global scope) supaya tidak ada satupun query "bocor".
- Schema-per-tenant / DB-per-tenant lebih aman by-design tapi over-engineering untuk kasus ini & nambah kompleksitas migration/deployment yang tidak sepadan dengan scope soal.

---

## 2b. Frontend: Laravel Blade + Livewire (opsional, demo layer)

**Penting untuk diketahui dulu:** soal ini secara eksplisit minta **backend** REST API — rubrik penilaian sama sekali tidak menyebut frontend. Jadi frontend ini **bukan requirement**, murni nilai tambah buat demo. Jangan sampai waktu habis di sini sebelum 6 requirement wajib + 3 test selesai.

**Pilihan:** Blade + **Livewire** (bukan Blade + Alpine/fetch manual ke API sendiri).

**Kenapa Livewire, bukan konsumsi API via JS:**
- Livewire jalan di atas session Laravel yang sama → tidak perlu bikin ulang layer auth/token di sisi frontend, tinggal reuse `Auth::user()` dan Policy yang sama persis dengan backend.
- Jauh lebih cepat dibangun (server-rendered component, tanpa state management JS terpisah) — pas untuk sisa waktu terbatas.
- Trade-off yang perlu disebut di README: Livewire membuat frontend **tidak benar-benar mengonsumsi REST API `/api/v1/...` yang sudah dibuat** (dia langsung ke Eloquent/service layer via komponen server-side). Kalau mau frontend jadi bukti nyata API-nya jalan, alternatifnya Blade + Alpine.js yang `fetch()` ke endpoint `/api/v1/...` pakai token Sanctum — tapi ini lebih lama dikerjakan. Sebutkan pilihan ini secara sadar di README sebagai keputusan trade-off.

**Scope frontend minimal (kalau waktu masih ada setelah semua wajib beres):**
1. Halaman login/register (Livewire component, submit ke logic yang sama dengan `AuthController` atau service class yang di-share).
2. Halaman list project (scoped ke company user login — otomatis lewat Global Scope yang sama).
3. Halaman detail project + list task, dengan aksi assign task (admin) dan update status task (member, hanya task miliknya) — mapping langsung ke Policy yang sama dengan backend.
4. Styling seadanya pakai Tailwind (sudah default di Laravel breeze/starter) — tidak perlu bagus, cukup fungsional untuk demo.

**Struktur kode:** taruh logic bisnis (create project, assign task, dll) di **Service class / Action class** yang dipakai bersama oleh API Controller maupun Livewire Component — supaya tidak duplikasi logic dan tetap konsisten dengan poin penilaian "struktur kode: logic terpisah dari controller".

**Kalau waktu benar-benar mepet:** skip frontend sepenuhnya, cukup tulis di README bahwa frontend di-skip demi fokus ke backend sesuai bobot penilaian, dan jelaskan API bisa dicoba lewat Postman collection / curl examples.

---

## 3. Skema Database

### Entities
```
Company
  id, name, created_at, updated_at

User
  id, company_id (FK), name, email (unique per company atau global — pilih global lebih simple),
  password, role (enum: admin, member), created_at, updated_at

Project
  id, company_id (FK), name, description, created_by (FK -> users.id),
  created_at, updated_at

Task
  id, project_id (FK), company_id (FK, denormalized untuk mempercepat scoping & index),
  title, description, status (enum: todo/in_progress/done),
  assigned_to (FK -> users.id, nullable),
  created_by (FK -> users.id),
  created_at, updated_at
```

**Catatan desain:**
- `company_id` didenormalisasi ke tabel `tasks` juga (bukan cuma lewat `project.company_id`) supaya global scope & index bisa langsung filter tanpa join — lebih cepat & lebih aman (defense in depth).
- Index minimal: `users(company_id)`, `projects(company_id)`, `tasks(company_id)`, `tasks(project_id)`, `tasks(assigned_to)`.
- Sertakan file migration Laravel sebagai pengganti ERD formal (lebih cepat, dan penguji Laravel-friendly bisa langsung baca).

---

## 4. Auth + Tenant Isolation

- Registrasi: buat Company baru + User pertama sebagai `admin` (flow signup standar SaaS: 1 form bikin company + admin sekaligus).
- Login: Sanctum, return token.
- Middleware: semua route `/api/v1/*` (kecuali auth) wajib `auth:sanctum`.
- Tenant resolve: **selalu dari `auth()->user()->company_id`**, tidak pernah trust input dari client.
- Global Scope (lihat bagian 2) jadi garda utama.
- Policy tambahan di controller untuk RBAC (lihat bagian 6).

---

## 5. Endpoint (REST) — `/api/v1/...`

```
POST   /api/v1/auth/register       -> daftar company + admin user
POST   /api/v1/auth/login          -> login, return token
POST   /api/v1/auth/logout

GET    /api/v1/projects            -> list project milik company sendiri
POST   /api/v1/projects            -> create (admin only)
GET    /api/v1/projects/{id}       -> detail (harus scoped)
PATCH  /api/v1/projects/{id}       -> update (admin only)
DELETE /api/v1/projects/{id}       -> delete (admin only)

GET    /api/v1/projects/{id}/tasks       -> list task dalam project
POST   /api/v1/projects/{id}/tasks       -> create task (admin only)
GET    /api/v1/projects/{id}/tasks/{taskId}
PATCH  /api/v1/projects/{id}/tasks/{taskId}   -> admin bebas; member hanya jika assigned_to = dirinya
DELETE /api/v1/projects/{id}/tasks/{taskId}   -> admin only
```

**Response envelope (konsisten di semua endpoint):**
```json
// Success
{ "success": true, "data": { ... }, "message": "OK" }

// Error
{ "success": false, "message": "Forbidden", "errors": null }
```
Buat lewat `app/Http/Resources` + custom `ApiResponse` helper/trait supaya konsisten tanpa nulis manual tiap controller.

---

## 6. RBAC — 2 Role

| Role | Project | Task |
|---|---|---|
| **admin** | full CRUD semua project di company-nya, kelola user | full CRUD semua task di company-nya |
| **member** | hanya read (list/detail) project di company-nya | hanya bisa update task yang `assigned_to` = dirinya; tidak bisa create/delete task atau project |

Implementasi: Laravel **Policy** per model (`ProjectPolicy`, `TaskPolicy`), register di `AuthServiceProvider`, dipanggil via `$this->authorize()` di controller — bukan if-else manual bertebaran (ini yang dinilai di "struktur kode: logic terpisah dari controller").

---

## 7. Background Job

**Scope minimal:** saat task di-assign ke user (create atau update `assigned_to`), dispatch job `SendTaskAssignedNotification` ke queue (`database` driver).
- Job cukup log ke console/tabel `notifications` — tidak perlu email betulan.
- Yang dinilai: job **keluar dari request-response cycle** (pakai `dispatch()`, bukan dijalankan sync di controller).

---

## 8. Testing (WAJIB, minimal 3, prioritas tertinggi setelah struktur dasar jalan)

1. **Tenant isolation test (paling penting):** User Company A coba GET/PATCH resource milik Company B → harus 403/404, bukan 200. Ini test yang paling menentukan nilai.
2. Member tidak bisa delete project (403).
3. Validasi input gagal saat create project/task dengan data tidak lengkap (422).

Gunakan Laravel **Feature Test** (hit endpoint via HTTP, bukan unit test terisolasi) supaya realistis membuktikan end-to-end scoping.

---

## 9. README — checklist wajib
- [ ] Cara run: `.env.example`, `php artisan migrate --seed`, `php artisan serve`, `php artisan queue:work`, `php artisan test`
- [ ] Penjelasan strategi multi-tenancy + trade-off (rangkum bagian 2 di atas)
- [ ] Apa yang di-skip karena waktu (contoh: audit trail, race condition handling, CI/Docker — jujur saja kalau tidak sempat)
- [ ] Satu keputusan teknis yang sempat ragu-ragu (contoh: kolom `company_id` didenormalisasi ke `tasks` — trade-off antara redundansi data vs kecepatan query & keamanan scoping)
- [ ] Seed data: minimal 2 company, masing-masing 1 admin + 1 member, beberapa project & task — supaya penguji bisa langsung coba tenant isolation manual juga

---

## 10. Nilai Plus (kerjakan HANYA kalau requirement wajib sudah selesai & teruji)
Urutan prioritas kalau ada sisa waktu:
1. Index kolom yang sering di-query (sudah masuk skema di atas — gratis, kerjakan dari awal)
2. Migration reversible (`down()` diisi benar — juga gratis, biasakan dari awal)
3. Dockerfile sederhana (docker-compose: app + mysql)
4. Audit trail sederhana (tabel `activity_logs`: who/what/when)
5. Penjelasan race condition di README (contoh: dua admin update task bersamaan) — cukup dijelaskan, tidak wajib diimplementasi kalau waktu mepet

---

## 11. Rencana Kerja / Milestone (target ~5 jam efektif)

| Waktu | Milestone |
|---|---|
| 0:00-0:30 | Setup project Laravel, `.env`, migration Company/User/Project/Task + index |
| 0:30-1:15 | Auth (register/login Sanctum) + Global Scope `company_id` |
| 1:15-2:30 | CRUD Project & Task + Policy RBAC + response envelope konsisten |
| 2:30-3:00 | Background job assign notification (queue) |
| 3:00-4:00 | **Testing — mulai dari tenant isolation test dulu**, baru 2 test lain |
| 4:00-4:45 | Seed/fixture data + README lengkap |
| 4:45-5:00 | Review commit history (harus bertahap, bukan 1 dump), final check |
| *(opsional, hanya kalau semua di atas sudah beres)* | 5:00-6:00 | Frontend Blade + Livewire minimal (login, list project, detail task) |

**Prinsip kalau waktu mulai mepet:** stop nambah fitur (termasuk frontend), pastikan 3 test wajib hijau + README jujur soal apa yang di-skip. Itu lebih tinggi nilainya daripada frontend cantik tapi backend setengah jadi — inget, rubrik penilaian fokus 100% ke backend.

---

## 12. Commit Strategy
Commit bertahap sesuai milestone di atas (jangan 1 commit besar di akhir):
```
chore: init laravel project + migration schema
feat: auth register/login with sanctum
feat: company scoping via global scope
feat: project CRUD + policy
feat: task CRUD + policy + assignment
feat: dispatch task assigned notification job
test: tenant isolation feature test
test: rbac + validation tests
docs: readme + seed data
```
