# Reasons API

## Overview

This endpoint group manages reason data using JWT authentication. Reason deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all reason endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Route Location

Reason API routes are defined in `routes/api.php`.

## Endpoints

### GET `/api/reasons`

Get paginated reason data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login
- `division_id` optional

### GET `/api/reason_active`

Get paginated reason data where `status != 11`.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login
- `division_id` optional

### GET `/api/reasons/{id}`

Get a single reason detail.

Detail responses include assigned divisions and parts/equipment:

```json
{
  "division_id": ["1", "2"],
  "division_name": ["Mechanical", "Electrical"],
  "part_id": ["1", "2"],
  "part_name": ["HC BLOWER", "HC EXCHANGER"]
}
```

### POST `/api/reasons`

Create a new reason.

Request body:

```json
{
  "code": "RSN001",
  "name": "Alasan A",
  "division_id": [1, 2],
  "part_id": [1, 2],
  "status": 1
}
```

### PUT `/api/reasons/{id}`

Update a reason.

Request body:

```json
{
  "area_id": 1,
  "code": "RSN001",
  "name": "Alasan A Update",
  "division_id": [1, 2],
  "part_id": [1, 2],
  "status": 1
}
```

When `division_id` or `part_id` is provided, the reason relation is replaced with the submitted IDs. The legacy `reasons.division_id` column stores the first submitted division ID for backward compatibility.

### DELETE `/api/reasons/{id}`

Delete a reason logically by changing `status` to `99`.

### PUT `/api/reason_setstatus/{id}`

Toggle reason status between `99` and `1`.

## Notes

- Active reason queries exclude records with `status = 99`.
- `GET /api/reason_active` excludes records with `status = 11`.
- `PUT /api/reason_setstatus/{id}` only supports current status `1` and `99`.
- `GET` dan `POST` menggunakan `area_id` dari user login.
- `division_id` optional dan bisa berisi satu atau lebih `division.id` aktif dari area reason.
- `part_id` optional dan bisa berisi satu atau lebih `part.id` aktif dari area reason.
- Validation is required for create and update requests.
