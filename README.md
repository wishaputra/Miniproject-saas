# Mini Project Management SaaS Backend

Ini adalah sistem backend multi-tenant untuk aplikasi manajemen proyek skala kecil (seperti Trello/Asana), dibangun sebagai pemenuhan Take-Home Test Dimensi Software. Fokus utama dari backend ini adalah keamanan data antar perusahaan (tenant isolation) melalui implementasi *row-level scoping*.

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
   Gunakan dua terminal (console) berbeda. Terminal pertama untuk menjalankan API server:
   ```bash
   php artisan serve
   ```
   Terminal kedua untuk menjalankan Queue Worker. Ini wajib dijalankan agar background job pengiriman notifikasi (saat task di-assign) bisa diproses.
   ```bash
   php artisan queue:work
   ```

6. **Jalankan Automated Tests (Opsional tapi disarankan)**
   Untuk membuktikan bahwa aturan Tenant Isolation dan Role-Based Access Control (RBAC) berfungsi sesuai syarat, jalankan:
   ```bash
   php artisan test
   ```

---

## 🔐 Kredensial Uji Coba (Seeded Data)

Data ini otomatis dibuat saat Anda menjalankan `php artisan migrate:fresh --seed`.
Password untuk **semua akun** di bawah ini adalah: `password`

### Company 1: PT Contoh Alpha
- **Admin**: `admin@alpha.com`
- **Member 1**: `budi@alpha.com`
- **Member 2**: `siti@alpha.com`

### Company 2: PT Contoh Beta
- **Admin**: `admin@beta.com`
- **Member 1**: `joko@beta.com`
- **Member 2**: `ani@beta.com`

*Gunakan salah satu dari akun ini untuk mendapatkan Token Sanctum (lihat bagian API Examples di bawah).*

---

## 🏛️ Strategi Multi-Tenancy (Row-Level Scoping)

Karena semua data client (tenant) disimpan di satu database dan tabel yang sama (Single Database Shared Schema), saya menggunakan pendekatan **Row-Level Scoping**. Setiap tabel utama (seperti `projects`, `tasks`, dan `users`) memiliki kolom `company_id`.

Agar developer tidak lupa menambahkan `WHERE company_id = ?` di setiap query API, saya menggunakan mekanisme **Global Scope** di Laravel bernama `CompanyScope`. Mekanismenya sebagai berikut:
1. Ketika query apapun dijalankan pada model yang dilindungi, `CompanyScope` otomatis menyuntikkan klausa `WHERE company_id = auth()->user()->company_id`.
2. Pengisian nilai `company_id` saat `INSERT` ditangani secara diam-diam (otomatis) lewat *model event* di dalam trait `BelongsToCompany`.
3. Hasilnya: Nilai `company_id` sama sekali **tidak pernah diambil dari input API Request (body maupun parameter URL)**. Semua bergantung 100% dari token autentikasi.

**Trade-off Strategi Ini:**
- Kelebihan: Arsitektur sederhana, performa cepat, perombakan database minim, query otomatis terlindungi tanpa harus ingat menambah sintaks filter.
- Kekurangan: Developer *rawan* membocorkan data jika mereka menggunakan operasi raw SQL (`DB::statement()`) atau men-disable global scope lewat `->withoutGlobalScope()`. Sistem ini membutuhkan kedisiplinan menggunakan Eloquent ORM secara ketat.

---

## ⚖️ Keputusan Teknis Penting: Denormalisasi `company_id` ke Tabel Tasks

Pada awalnya saya sempat ragu apakah tabel `tasks` perlu memiliki kolom `company_id`. Secara teori normalisasi relasional (3NF), tabel task cukup memiliki `project_id`. Kita bisa tahu task itu milik company apa dengan cara menengok `company_id` yang ada di tabel `projects` induknya (via JOIN).

Namun, saya memutuskan untuk **mendenormalisasi** (menambahkan `company_id` langsung ke dalam tabel `tasks`).
**Alasan Trade-off:**
1. **Performa & Simplifikasi Security:** Jika `company_id` ada langsung di tabel tasks, global `CompanyScope` bisa bekerja langsung (`WHERE tasks.company_id = ?`). Jika tidak didenormalisasi, setiap query task harus selalu melakukan query `JOIN projects`. Di skala tabel task yang jutaan row (sering terjadi di aplikasi SaaS), implicit JOIN semacam ini berisiko memperlambat performa read secara signifikan.
2. Kelemahan pendekatan ini adalah kita harus selalu memastikan nilai `company_id` sinkron dengan `project_id`. Saya menangani kelemahan ini dengan validasi berlapis di level backend.

## 🚀 Penilaian Plus (Bonus Points)

Sesuai kriteria PDF bagian "Nilai Plus", fitur berikut telah diimplementasikan:
1. **Hindari N+1 Query & Indexing:** 
   - Semua relasi di API (seperti `assignee`, `project`, `creator`) dimuat menggunakan `.with()` (Eager Loading).
   - Kolom ForeignKey `company_id`, `project_id`, dan `assigned_to` sudah di-*index* di sisi Database (Migration).
2. **Audit Trail (Re-assign Task):**
   - Admin diwajibkan menyertakan **Note / Alasan** ketika menggeser (*re-assign*) tugas yang sudah berstatus *Done* atau *In Progress*. Riwayat dicatat secara terpisah di tabel `task_reassignment_logs`.
3. **Penanganan Race Condition (Pessimistic Locking):**
   - **Masalah:** Dua pengguna bisa saja secara bersamaan mengupdate status/memindahkan tugas di waktu/milidetik yang sama (Race condition).
   - **Solusi:** Saya membungkus proses `update` dan `reassign` pada `TaskService` menggunakan `DB::transaction()` serta metode `->lockForUpdate()`. 
   - **Trade-off:** `lockForUpdate()` akan membuat query dari pengguna ke-2 menunggu (mengantri) baris data tersebut dilepaskan oleh transaksi pengguna ke-1 (Pessimistic Locking). Sangat aman dari data korup, walau sedikit menambah latency jika sistem sangat ramai di detik yang sama.
4. **Migration yang Reversible:**
   - Semua kelas migrasi memiliki fungsi `down()` yang valid.
5. **Contoh CI/CD & Containerization:**
   - **GitHub Actions:** Terdapat file konfigurasi `.github/workflows/tests.yml` untuk memicu *automated testing* saat kode di-push ke branch master/main.
   - **Dockerfile:** Terdapat contoh `Dockerfile` lengkap dengan Multi-stage build menggunakan Nginx & PHP 8.3-FPM Alpine siap untuk deployment ke arsitektur *Cloud*.



## 🔌 API Request Examples

Untuk memudahkan pengujian via Postman, cURL, atau ThunderClient. Pastikan Anda menyertakan Header `Accept: application/json` pada semua request.

### 1. Register Company & Admin Baru
```curl
curl -X POST http://127.0.0.1:8000/api/v1/auth/register \
-H "Accept: application/json" \
-H "Content-Type: application/json" \
-d '{
    "company_name": "PT Jaya Abadi",
    "name": "Bapak CEO",
    "email": "ceo@jayaabadi.com",
    "password": "password",
    "password_confirmation": "password"
}'
```
*(Simpan nilai token dari respons JSON, Anda butuh ini untuk request selanjutnya)*

### 2. Login
```curl
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
-H "Accept: application/json" \
-H "Content-Type: application/json" \
-d '{
    "email": "admin@alpha.com",
    "password": "password"
}'
```

### 3. List Semua Project (Menggunakan Token)
```curl
curl -X GET http://127.0.0.1:8000/api/v1/projects \
-H "Accept: application/json" \
-H "Authorization: Bearer <TULIS_TOKEN_ANDA_DISINI>"
```

### 4. Create Project (Hanya bisa oleh Admin)
```curl
curl -X POST http://127.0.0.1:8000/api/v1/projects \
-H "Accept: application/json" \
-H "Content-Type: application/json" \
-H "Authorization: Bearer <TULIS_TOKEN_ANDA_DISINI>" \
-d '{
    "name": "Project Penting Tahun Ini",
    "description": "Fokus pada Q3."
}'
```

### 5. Create Member Baru (Admin Only)
```curl
curl -X POST http://127.0.0.1:8000/api/v1/users \
-H "Accept: application/json" \
-H "Content-Type: application/json" \
-H "Authorization: Bearer <TULIS_TOKEN_ANDA_DISINI>" \
-d '{
    "name": "Staff Baru",
    "email": "staff@alpha.com",
    "password": "password",
    "password_confirmation": "password"
}'
```
*(Member yang baru dibuat otomatis akan masuk ke company yang sama dengan Admin, dan rolenya pasti 'member').*
