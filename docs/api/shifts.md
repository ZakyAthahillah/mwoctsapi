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
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/shift_active`

Get paginated shift data where `status != 11`.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/shifts/{id}`

Get a single shift detail.

### POST `/api/shifts`

Create a new shift.

Request body:

```json
{
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

### PUT `/api/shift_setstatus/{id}`

Toggle shift status between `99` and `1`.

## Notes

- Active shift queries exclude records with `status = 99`.
- `GET /api/shift_active` excludes records with `status = 11`.
- `PUT /api/shift_setstatus/{id}` only supports current status `1` and `99`.
- `GET` dan `POST` menggunakan `area_id` dari user login.
- `time_start` and `time_finish` use `HH:MM` format in request payloads.
- Validation is required for create and update requests.
