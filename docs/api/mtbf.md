# MTBF API

## Overview

Endpoint ini menyediakan data MTBF dalam beberapa mode agregasi:

- yearly
- monthly
- daily
- shift
- taskplus

## Authentication

Gunakan JWT bearer token.

## Endpoints

### GET `/api/mtbf`

Query parameters umum:

- `type` required: `yearly`, `monthly`, `daily`, `shift`
- `machine_id` optional
- `position_id` optional
- `part_id` optional
- `part_serial_number_id` optional
- `is_taskplus` optional boolean

Parameter tambahan berdasarkan type:

- `year` required untuk `monthly` dan `daily`
- `month` required untuk `daily`
- `period_start` dan `period_end` required untuk `shift`

Contoh:

```text
GET /api/mtbf?type=yearly
GET /api/mtbf?type=monthly&year=2026
GET /api/mtbf?type=daily&year=2026&month=4
GET /api/mtbf?type=daily&year=2026&month=4&is_taskplus=1
GET /api/mtbf?type=shift&period_start=2026-04-01&period_end=2026-04-07
```

### GET `/api/mtbf/taskplus`

Ambil rekap MTBF taskplus per bulan untuk satu tahun.

Query parameters:

- `year` required

Contoh:

```text
GET /api/mtbf/taskplus?year=2026
```

Catatan:
- Area dibatasi otomatis berdasarkan `area_id` user yang login.
