# K6Testing

Kumpulan script K6 untuk smoke test dan load test API MWOCTS.

## Prasyarat

Install K6 terlebih dahulu:

```powershell
winget install k6.k6
```

Jalankan Laravel API di terminal lain:

```powershell
php artisan serve
```

Default Laravel URL biasanya:

```txt
http://127.0.0.1:8000
```

## Environment

Script membutuhkan akun yang sudah ada di database.

```powershell
$env:BASE_URL = "http://127.0.0.1:8000"
$env:K6_EMAIL = "admin@example.com"
$env:K6_PASSWORD = "password"
```

Opsional:

```powershell
$env:K6_PER_PAGE = "10"
$env:K6_PERIOD_START = "2026-04-01"
$env:K6_PERIOD_END = "2026-04-30"
$env:K6_LOGIN_P95_MS = "1000"
$env:K6_LOGIN_SLEEP_SECONDS = "1"
```

## Cara Test

Jalankan menu interaktif:

```powershell
.\run-tests.bat
```

Atau double-click file `run-tests.bat` dari File Explorer. Jika `K6_EMAIL` dan `K6_PASSWORD` belum diset, runner akan meminta input terlebih dahulu.

Smoke test ringan untuk memastikan login dan endpoint GET utama berjalan:

```powershell
k6 run K6Testing/scenarios/smoke.js
```

Baseline test untuk beban kecil:

```powershell
k6 run K6Testing/scenarios/baseline.js
```

Load test bertahap:

```powershell
k6 run K6Testing/scenarios/load.js
```

Load test khusus endpoint login:

```powershell
k6 run K6Testing/scenarios/login.js
```

Skenario login berjalan bertahap dari 1, 5, 10, sampai 20 VUs. Ini membantu melihat kapan endpoint mulai antre. Jika 1 VU cepat tetapi 10 atau 20 VUs lambat, bottleneck biasanya ada di proses login yang CPU-bound seperti password hashing.

Load test khusus endpoint machines:

```powershell
k6 run K6Testing/scenarios/machines.js
```

Stress test untuk mencari batas performa:

```powershell
k6 run K6Testing/scenarios/stress.js
```

Spike test untuk simulasi lonjakan request:

```powershell
k6 run K6Testing/scenarios/spike.js
```

## Catatan Aman

Script default hanya memakai endpoint `POST /api/login` dan endpoint `GET`.
Tidak ada request create, update, atau delete agar data test tidak berubah.
