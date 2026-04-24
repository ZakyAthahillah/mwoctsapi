# Monitor API

## Overview

Endpoint ini menampilkan data monitor pekerjaan maintenance untuk area user yang login. Data utama diambil dari rentang `period_start` sampai `period_end`, lalu ditambah data tiga hari sebelumnya yang masih perlu dipantau (`status <= 2` atau `status = 4`).

## Authentication

Gunakan JWT bearer token.

```http
Authorization: Bearer <token>
Accept: application/json
```

## Endpoints

### GET `/api/monitor`

Query parameters:

- `period_start` optional, format date, default satu hari sebelum `period_end`
- `period_end` optional, format date, default tanggal hari ini
- `page` optional, default `1`
- `per_page` optional, default `10`, max `100`

Example:

```http
GET /api/monitor?period_start=2026-04-20&period_end=2026-04-21&per_page=10
Authorization: Bearer <token>
Accept: application/json
```

Notes:

- Data otomatis dibatasi berdasarkan `area_id` user login.
- Response menggunakan format standar API.
- Endpoint menggunakan pagination standar API.
