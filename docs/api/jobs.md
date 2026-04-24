# Jobs API

## Overview

Endpoint ini menangani daftar pekerjaan maintenance dan seluruh alur kerja job:

- list job per status
- detail job
- start job
- restart job dari status extend
- extend job
- finish job
- approve job

## Authentication

Gunakan JWT bearer token.

## Endpoints

### GET `/api/jobs`

List job dengan pagination.

Query parameters:

- `per_page` optional
- `status` optional: `new`, `on_progress`, `extend`, `waiting_for_approval`, `finish`
- `division_id` optional
- `machine_id` optional
- `position_id` optional
- `part_id` optional
- `operation_id` optional
- `reason_id` optional
- `informant_id` optional
- `reporting_type` optional
- `technician_id` optional
- `search` optional

Catatan:
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/jobs/new`

List job dengan status `new`.

Query parameters:

- `per_page` optional
- `division_id` optional
- `machine_id` optional
- `position_id` optional
- `part_id` optional
- `operation_id` optional
- `reason_id` optional
- `informant_id` optional
- `reporting_type` optional
- `technician_id` optional
- `search` optional

### GET `/api/jobs/on-progress`

List job dengan status `on_progress`.

### GET `/api/jobs/extend`

List job dengan status `extend`.

### GET `/api/jobs/waiting-for-approval`

List job dengan status `waiting_for_approval`.

### GET `/api/jobs/finish`

List job dengan status `finish`.

### GET `/api/jobs/{job}`

Ambil detail satu job.

### PUT `/api/jobs/{job}/start`

Mulai job dari status `new`.

```json
{
  "technician_id": 1,
  "processing_date_start": "2026-04-17 09:00:00"
}
```

### PUT `/api/jobs/{job}/start-extend`

Mulai ulang job dari status `extend`.

```json
{
  "technician_id": 1,
  "processing_date_start": "2026-04-17 13:00:00"
}
```

### PUT `/api/jobs/{job}/extend`

Ubah job `on_progress` ke status `extend`.

```json
{
  "processing_date_finish": "2026-04-17 15:30:00",
  "notes": "Butuh spare part tambahan"
}
```

### PUT `/api/jobs/{job}/finish`

Selesaikan job dari status `on_progress`.

```json
{
  "processing_date_finish": "2026-04-17 15:30:00",
  "operation_id_actual": 1,
  "notes": "Pekerjaan selesai",
  "is_change_part": false
}
```

Jika part diganti:

```json
{
  "processing_date_finish": "2026-04-17 15:30:00",
  "operation_id_actual": 1,
  "notes": "Ganti part",
  "is_change_part": true,
  "part_serial_number_id": 10
}
```

### PUT `/api/jobs/{job}/approve`

Approve job dari status `waiting_for_approval`.

```json
{
  "approved_at": "2026-04-17 16:00:00",
  "approved_by": 5,
  "approved_notes": "Sudah sesuai"
}
```
