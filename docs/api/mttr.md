# MTTR API

## Overview

Endpoint ini menyediakan data MTTR dalam beberapa mode agregasi:

- yearly
- monthly
- daily
- shift

## Authentication

Gunakan JWT bearer token.

## Endpoints

### GET `/api/mttr`

Query parameters umum:

- `type` required: `yearly`, `monthly`, `daily`, `shift`
- `machine_id` optional
- `position_id` optional
- `part_id` optional
- `operation_id` optional

Parameter tambahan berdasarkan type:

- `year` required untuk `monthly` dan `daily`
- `month` required untuk `daily`
- `period_start` dan `period_end` required untuk `shift`

Contoh:

```text
GET /api/mttr?type=yearly
GET /api/mttr?type=monthly&year=2026
GET /api/mttr?type=daily&year=2026&month=4
GET /api/mttr?type=shift&period_start=2026-04-01&period_end=2026-04-07
```

Catatan:
- Area dibatasi otomatis berdasarkan `area_id` user yang login.
