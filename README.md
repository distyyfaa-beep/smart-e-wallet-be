# 💸 Mini Wallet — Backend (Laravel 12 + Sanctum)

## Tech Stack
- **PHP** 8.2+
- **Laravel** 12
- **Laravel Sanctum** (token-based API auth)
- **SQLite** (database file: `database/database.sqlite`)

## Setup & Run

```bash
# 1. Masuk ke folder
cd wallet-be

# 2. Install dependencies (pertama kali)
composer install

# 3. Copy env & generate key (kalau belum ada .env)
copy .env.example .env
php artisan key:generate

# 4. Jalankan migrasi database
php artisan migrate

# 5. Jalankan server
php artisan serve
# → http://localhost:8000
```

## API Endpoints

| Method | Endpoint            | Auth | Keterangan                    |
|--------|---------------------|------|-------------------------------|
| POST   | `/api/register`     | ❌   | Daftar (username, email, pw)  |
| POST   | `/api/login`        | ❌   | Login → return token          |
| POST   | `/api/logout`       | ✅   | Hapus token                   |
| GET    | `/api/wallet`       | ✅   | Lihat saldo                   |
| POST   | `/api/topup`        | ✅   | Tambah saldo                  |
| POST   | `/api/transfer`     | ✅   | Transfer ke user lain         |
| GET    | `/api/transactions` | ✅   | Riwayat mutasi                |

> ✅ = wajib pakai `Authorization: Bearer <token>` di header

## Validasi Penting
- `register` → email format, password min:8, username unique
- `topup/transfer amount` → integer, min:1, max:100000000
- Transfer pakai **DB Transaction** (auto rollback jika gagal)
- User A tidak bisa lihat transaksi User B

## Error HTTP Status
- `401` Unauthorized
- `422` Unprocessable Entity (validasi)
- `400` Bad Request (saldo tidak cukup, dst)
- `404` Not Found (user penerima tidak ada)
