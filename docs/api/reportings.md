# Reportings API

## Overview

This endpoint group manages downtime report submissions using JWT authentication. Deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all reporting endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Endpoints

### GET `/api/reportings`

Get paginated reporting data for the authenticated user's area. Records with `status = 1` (`new`/`baru`) and `status = 99` (`deleted`) are not returned.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- `division_id` optional
- `machine_id` optional
- `position_id` optional
- `part_id` optional
- `part_serial_number_id` optional
- `operation_id` optional
- `reason_id` optional
- `informant_id` optional
- `reporting_type` optional
- `status` optional

### GET `/api/reportings/{id}`

Get a single reporting detail.

### POST `/api/reportings`

Create a new reporting.

Request body:

```json
{
  "machine_id": 1,
  "position_id": 1,
  "part_id": 1,
  "part_serial_number_id": 1,
  "division_id": 1,
  "operation_id": 1,
  "reason_id": 1,
  "informant_id": 1,
  "reporting_type": 1,
  "reporting_date": "2026-04-22 10:00:00",
  "reporting_notes": "Mesin abnormal"
}
```

### PUT `/api/reportings/{id}`

Update a reporting.

Request body:

```json
{
  "machine_id": 1,
  "position_id": 1,
  "part_id": 1,
  "part_serial_number_id": 1,
  "division_id": 1,
  "operation_id": 1,
  "reason_id": 1,
  "informant_id": 1,
  "reporting_type": 1,
  "reporting_date": "2026-04-22 10:00:00",
  "reporting_notes": "Catatan pelaporan update"
}
```

### DELETE `/api/reportings/{id}`

Delete a reporting logically by changing `status` to `99`.

### GET `/api/reportings/types`

Get reporting type options.

### GET `/api/reportings/time`

Get active shift data for a reporting date.

Query parameters:

- `reporting_date` optional, defaults to current server time

## Notes

- `reporting_date` is used to resolve `shift_id_reporting`.
- The API reads and writes the existing `reportings` table.
- Created reportings start with `status = 1`.
- `GET /api/reportings` excludes new reportings with `status = 1`.
