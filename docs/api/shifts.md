# Shifts API

## Overview

This endpoint group manages shift data using JWT authentication. Shift deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all shift endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Route Location

Shift API routes are defined in `routes/api.php`.

## Endpoints

### GET `/api/shifts`

Get paginated shift data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- `area_id` optional

### GET `/api/shifts/{id}`

Get a single shift detail.

### POST `/api/shifts`

Create a new shift.

Request body:

```json
{
  "area_id": 1,
  "name": "Shift Pagi",
  "time_start": "08:00",
  "time_finish": "16:00",
  "status": 1
}
```

### PUT `/api/shifts/{id}`

Update a shift.

### DELETE `/api/shifts/{id}`

Delete a shift logically by changing `status` to `99`.

## Notes

- Active shift queries exclude records with `status = 99`.
- `area_id` may be `null`.
- `time_start` and `time_finish` use `HH:MM` format in request payloads.
- Validation is required for create and update requests.
