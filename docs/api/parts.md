# Parts API

## Overview

This endpoint group manages part data using JWT authentication. Part deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all part endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Route Location

Part API routes are defined in `routes/api.php`.

## Endpoints

### GET `/api/parts`

Get paginated part data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/part_active`

Get paginated part data where `status != 11`.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/parts/{id}`

Get a single part detail.

Detail responses include assigned operations and reasons:

```json
{
  "operation_id": ["1", "2"],
  "operation_name": ["Check Motor", "Clean Unit"],
  "reason_id": ["1", "2"],
  "reason_name": ["Broken", "Leaking"]
}
```

### POST `/api/parts`

Create a new part.

Request body:

```json
{
  "code": "PRT001",
  "name": "Part A",
  "description": "Deskripsi part",
  "operation_id": [1, 2],
  "reason_id": [1, 2],
  "status": 1
}
```

### PUT `/api/parts/{id}`

Update a part.

Request body:

```json
{
  "area_id": 1,
  "code": "PRT001",
  "name": "Part A Update",
  "description": "Deskripsi part update",
  "operation_id": [1, 2],
  "reason_id": [1, 2],
  "status": 1
}
```

When `operation_id` or `reason_id` is provided, the part relation is replaced with the submitted IDs.

### DELETE `/api/parts/{id}`

Delete a part logically by changing `status` to `99`.

### PUT `/api/part_setstatus/{id}`

Toggle part status between `99` and `1`.

## Notes

- Active part queries exclude records with `status = 99`.
- `GET /api/part_active` excludes records with `status = 11`.
- `PUT /api/part_setstatus/{id}` only supports current status `1` and `99`.
- `GET` dan `POST` menggunakan `area_id` dari user login.
- `description` may be `null`.
- Validation is required for create and update requests.
