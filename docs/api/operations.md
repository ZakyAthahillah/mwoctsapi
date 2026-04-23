# Operations API

## Overview

This endpoint group manages operation data using JWT authentication. Operation deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all operation endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Route Location

Operation API routes are defined in `routes/api.php`.

## Endpoints

### GET `/api/operations`

Get paginated operation data.
Each item also includes `total_division` and `total_part`.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/operation_active`

Get paginated operation data where `status != 11`.
Each item also includes `total_division` and `total_part`.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/operations/{id}`

Get a single operation detail.

Detail responses include assigned divisions and parts/equipment:

```json
{
  "division_id": ["1", "2"],
  "division_name": ["Mechanical", "Electrical"],
  "part_id": ["1", "2"],
  "part_name": ["HC BLOWER", "HC EXCHANGER"]
}
```

### POST `/api/operations`

Create a new operation.

Request body:

```json
{
  "code": "OPR001",
  "name": "Pekerjaan A",
  "division_id": [1, 2],
  "part_id": [1, 2],
  "status": 1
}
```

### PUT `/api/operations/{id}`

Update an operation.

Request body:

```json
{
  "area_id": 1,
  "code": "OPR001",
  "name": "Pekerjaan A Update",
  "division_id": [1, 2],
  "part_id": [1, 2],
  "status": 1
}
```

When `division_id` or `part_id` is provided, the operation relation is replaced with the submitted IDs.

### DELETE `/api/operations/{id}`

Delete an operation logically by changing `status` to `99`.

### PUT `/api/operation_setstatus/{id}`

Toggle operation status between `99` and `1`.

## Notes

- Active operation queries exclude records with `status = 99`.
- `GET /api/operation_active` excludes records with `status = 11`.
- `PUT /api/operation_setstatus/{id}` only supports current status `1` and `99`.
- `GET` dan `POST` menggunakan `area_id` dari user login.
- `GET /api/operations` dan `GET /api/operation_active` menampilkan `total_division` dan `total_part`.
- `division_id` optional dan bisa berisi satu atau lebih `division.id` aktif dari area operation.
- `part_id` optional dan bisa berisi satu atau lebih `part.id` aktif dari area operation.
- Validation is required for create and update requests.
