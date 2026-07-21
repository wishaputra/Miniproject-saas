# Mini Project Management SaaS Backend

Sistem backend multi-tenant untuk aplikasi manajemen proyek skala kecil (seperti Trello/Asana), dibangun sebagai pemenuhan Take-Home Test Dimensi Software. Fokus utama backend ini adalah keamanan data antar perusahaan (tenant isolation) melalui *row-level scoping*.

---

## 🚀 Cara Menjalankan Project (Setup Guide)

Pastikan sistem Anda sudah memiliki PHP 8.2+, Composer, dan MySQL/MariaDB yang siap digunakan.

1. **Clone repository ini**
   ```bash
   git clone https://github.com/wishaputra/Miniproject-saas.git
   cd Miniproject-saas
   ```

2. **Siapkan Environment Variables**
   Salin `.env.example` ke `.env` lalu sesuaikan konfigurasi database Anda (DB_DATABASE, DB_USERNAME, DB_PASSWORD).
   ```bash
   cp .env.example .env
   ```

3. **Install Dependencies & Generate Key**
   ```bash
   composer install
   php artisan key:generate
   ```

4. **Migrate dan Seed Database**
   Perintah ini akan membuat semua tabel dan mengisi database dengan data dummy agar bisa langsung dites (credentials ada di bawah).
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Jalankan Aplikasi dan Queue Worker**
   Gunakan dua terminal berbeda. Terminal pertama untuk menjalankan API server:
   ```bash
   php artisan serve
   ```
   Terminal kedua untuk menjalankan Queue Worker. Ini **wajib dijalankan** agar background job pengiriman notifikasi (saat task di-assign) bisa diproses:
   ```bash
   php artisan queue:work
   ```

6. **Jalankan Automated Tests**
   Untuk membuktikan bahwa Tenant Isolation dan Role-Based Access Control (RBAC) berfungsi sesuai syarat:
   ```bash
   php artisan test
   ```

7. **Akses Frontend (opsional, bonus demo layer)**
   Selain REST API, tersedia juga UI sederhana berbasis Blade + Livewire untuk demo — buka `http://127.0.0.1:8000/login` di browser setelah `php artisan serve` jalan. UI ini mengonsumsi Service layer yang sama dengan API (bukan requirement wajib di soal, dibangun sebagai nilai tambah demo).

---

## 🔐 Kredensial Uji Coba (Seeded Data)

Data ini otomatis dibuat saat menjalankan `php artisan migrate:fresh --seed`.
Password untuk **semua akun** di bawah ini adalah: `password`

### Company 1: PT Contoh Alpha
- **Admin**: `admin@alpha.com`
- **Member 1**: `budi@alpha.com`
- **Member 2**: `siti@alpha.com`

### Company 2: PT Contoh Beta
- **Admin**: `admin@beta.com`
- **Member 1**: `joko@beta.com`
- **Member 2**: `ani@beta.com`

*Gunakan salah satu akun ini untuk mendapatkan Token Sanctum (lihat bagian API Examples di bawah). Untuk membuktikan tenant isolation secara manual, coba akses resource company lain dengan token dari company yang berbeda — harus mendapat 404.*

---

## 🏛️ Strategi Multi-Tenancy (Row-Level Scoping)

Karena semua data tenant disimpan di satu database dan tabel yang sama (*single database, shared schema*), saya menggunakan pendekatan **Row-Level Scoping**. Setiap tabel utama (`projects`, `tasks`, `users`) memiliki kolom `company_id`.

Agar developer tidak perlu (dan tidak mungkin lupa) menambahkan `WHERE company_id = ?` secara manual di setiap query, saya membuat Global Scope Laravel bernama `CompanyScope`:
1. Ketika query apapun dijalankan pada model yang dilindungi, `CompanyScope` otomatis menyuntikkan klausa `WHERE company_id = auth()->user()->company_id`.
2. Pengisian nilai `company_id` saat `INSERT` ditangani otomatis lewat model event di trait `BelongsToCompany`.
3. Hasilnya: nilai `company_id` **tidak pernah diambil dari input API request** (body maupun parameter URL) — sepenuhnya bergantung pada token autentikasi.

**Trade-off strategi ini:**
- **Kelebihan:** arsitektur sederhana, performa cepat, perubahan skema minim, setiap query otomatis terlindungi tanpa perlu diingat manual.
- **Kekurangan:** developer tetap rawan membocorkan data jika menggunakan raw SQL (`DB::statement()`) atau sengaja men-disable global scope lewat `->withoutGlobalScope()`. Strategi ini menuntut disiplin penuh menggunakan Eloquent ORM untuk semua akses data tenant-scoped.
- **Dibanding schema-per-tenant / database-per-tenant:** dua pendekatan itu lebih aman *by design* (isolasi di level infrastruktur, bukan logic aplikasi), tapi jauh lebih kompleks untuk provisioning, migration, dan deployment — tidak sepadan untuk skala aplikasi ini.

---

## ⚖️ Keputusan Teknis yang Sempat Diragukan: Denormalisasi `company_id` ke Tabel Tasks

Awalnya saya ragu apakah tabel `tasks` perlu kolom `company_id` sendiri. Secara normalisasi relasional (3NF), cukup lewat `project_id` — company task bisa ditelusuri via JOIN ke `projects`.

Saya memutuskan **mendenormalisasi** (menambahkan `company_id` langsung ke tabel `tasks`), dengan alasan:
1. **Performa & simplifikasi security:** dengan `company_id` langsung di tabel `tasks`, `CompanyScope` bisa langsung `WHERE tasks.company_id = ?` tanpa perlu implicit JOIN ke `projects` di setiap query — penting untuk endpoint list task yang paling sering diakses.
2. **Trade-off/risiko:** nilai `company_id` di `tasks` harus selalu sinkron dengan `company_id` di `projects` induknya. Saya tangani ini dengan meng-set `company_id` task secara otomatis dari `project.company_id` saat create (bukan dari input), sehingga tidak mungkin tidak sinkron melalui jalur normal aplikasi.

---

## 🧩 Apa yang Di-skip / Dikorbankan Karena Waktu

*(Isi bagian ini jujur sesuai kondisi riil kamu — draft di bawah asumsi hampir semua requirement + nilai plus sudah selesai, sesuaikan kalau ada yang belum sempat.)*

- **Rate limiting per endpoint** belum diimplementasikan — Laravel sudah punya throttle middleware bawaan yang bisa langsung dipasang, tapi belum sempat dikonfigurasi & ditest secara khusus.
- **Postman/OpenAPI collection file** belum dibuat sebagai file terpisah — contoh request masih dalam bentuk curl di README ini saja.
- **Note wajib saat reassign task berstatus Done/In Progress** adalah business rule tambahan di luar requirement asli soal (bukan diminta di spesifikasi) — ditambahkan sebagai contoh penanganan audit trail yang lebih ketat. Kalau field `note` tidak dikirim saat reassign task dengan status tersebut, request akan [isi sesuai actual behavior: ditolak validasi / note bersifat opsional].
- Kalau ada waktu lebih, berikutnya saya akan tambahkan: test coverage untuk audit trail & race condition secara otomatis (saat ini keduanya sudah diimplementasi tapi belum ada test khusus yang memverifikasi perilakunya), serta styling frontend yang lebih rapi (saat ini fungsional, belum dipoles).

---

## 🚀 Nilai Plus (Bonus Points) yang Diimplementasikan

1. **Hindari N+1 Query & Indexing:**
   - Semua relasi di API (`assignee`, `project`, `creator`) dimuat menggunakan eager loading (`.with()`).
   - Kolom foreign key `company_id`, `project_id`, dan `assigned_to` sudah di-index di migration.
2. **Audit Trail (Activity Log):**
   - Setiap aksi Create, Update, Delete pada Project maupun Task otomatis tercatat di tabel `activity_logs` lewat Model Observer (`TaskObserver`, `ProjectObserver`).
   - Log ini tunduk penuh pada tenant isolation — Admin Company A tidak bisa melihat activity log Company B.
   - Ada UI khusus admin untuk melihat history log (via frontend Livewire).
   - Sebagai tambahan: reassign task yang berstatus Done/In Progress mewajibkan Note/alasan (lihat catatan di bagian "Di-skip" di atas — ini di luar requirement asli).
3. **Penanganan Race Condition (Pessimistic Locking):**
   - Update dan reassign task di `TaskService` dibungkus `DB::transaction()` + `->lockForUpdate()`.
   - **Trade-off:** request kedua yang datang bersamaan akan menunggu baris data dilepas oleh transaksi pertama (aman dari data korup), dengan sedikit tambahan latency di skenario concurrent tinggi.
4. **Migration Reversible:** semua migration punya fungsi `down()` yang valid.
5. **CI/CD & Containerization:**
   - GitHub Actions (`.github/workflows/tests.yml`) untuk automated testing saat push ke branch master/main.
   - `Dockerfile` multi-stage build (Nginx + PHP 8.3-FPM Alpine) siap deploy.

---

## 🔌 API Request Examples

Sertakan header `Accept: application/json` di semua request.

### Auth

**1. Register Company & Admin Baru**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/register \
-H "Accept: application/json" -H "Content-Type: application/json" \
-d '{
    "company_name": "PT Jaya Abadi",
    "name": "Bapak CEO",
    "email": "ceo@jayaabadi.com",
    "password": "password",
    "password_confirmation": "password"
}'
```
*(Simpan token dari respons — dibutuhkan untuk semua request berikutnya)*

**2. Login**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
-H "Accept: application/json" -H "Content-Type: application/json" \
-d '{ "email": "admin@alpha.com", "password": "password" }'
```

### Project

**3. List Project**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/projects \
-H "Accept: application/json" -H "Authorization: Bearer <TOKEN>"
```

**4. Create Project (Admin only)**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/projects \
-H "Accept: application/json" -H "Content-Type: application/json" \
-H "Authorization: Bearer <TOKEN>" \
-d '{ "name": "Project Penting Tahun Ini", "description": "Fokus pada Q3." }'
```

### Task

**5. List Task dalam Project**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/projects/1/tasks \
-H "Accept: application/json" -H "Authorization: Bearer <TOKEN>"
```

**6. Create Task (Admin only)**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/projects/1/tasks \
-H "Accept: application/json" -H "Content-Type: application/json" \
-H "Authorization: Bearer <TOKEN>" \
-d '{ "title": "Setup CI Pipeline", "description": "Tambah GitHub Actions", "assigned_to": 2 }'
```

**7. Update Status Task (Member — hanya task miliknya sendiri)**
```bash
curl -X PATCH http://127.0.0.1:8000/api/v1/projects/1/tasks/1 \
-H "Accept: application/json" -H "Content-Type: application/json" \
-H "Authorization: Bearer <TOKEN_MEMBER>" \
-d '{ "status": "in_progress" }'
```

### User Management

**8. Create Member Baru (Admin only)**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/users \
-H "Accept: application/json" -H "Content-Type: application/json" \
-H "Authorization: Bearer <TOKEN>" \
-d '{
    "name": "Staff Baru",
    "email": "staff@alpha.com",
    "password": "password",
    "password_confirmation": "password"
}'
```
*(Member baru otomatis masuk ke company yang sama dengan admin yang membuat, dan rolenya selalu `member`)*
