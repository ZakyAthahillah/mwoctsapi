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

Get paginated part data. Each item includes `total_operation`, `total_reason`, and `total_serial_number`.
This list includes both active parts and soft-deleted parts with `status = 99`.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/part_active`

Get paginated part data where `status != 11`. Each item includes `total_operation`, `total_reason`, and `total_serial_number`.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/parts/{id}`

Get a single part detail.

Detail responses include assigned operations and reasons:

```json
{
  "total_operation": 2,
  "total_reason": 2,
  "total_serial_number": 5,
  "operation_id": ["1", "2"],
  "operation_name": ["Check Motor", "Clean Unit"],
  "reason_id": ["1", "2"],
  "reason_name": ["Broken", "Leaking"]
}
```

### GET `/api/part/{id}/detail`

Legacy compatibility route for the same part detail response as `GET /api/parts/{id}`.

### GET `/api/part/get-data-array`

Get a lightweight part selector list.

Query parameters:

- `term` optional search term
- `search` optional alias for `term`

### GET `/api/part/get-full-data-array`

Get a full part selector list including `code`, `name`, and `description`.

Query parameters:

- `term` optional search term
- `search` optional alias for `term`

### GET `/api/part/{id}/get-operation`

Get operations assigned to a part.

Query parameters:

- `term` optional search term
- `search` optional alias for `term`

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

### Legacy compatibility examples

List part data array
GET /api/part/get-data-array?term=prt
Headers:
Authorization: Bearer <your_jwt_token>
Accept: application/json
Content-Type: application/json

List full part data array
GET /api/part/get-full-data-array?term=prt
Headers:
Authorization: Bearer <your_jwt_token>
Accept: application/json
Content-Type: application/json

Get legacy part detail
GET /api/part/{id}/detail
Headers:
Authorization: Bearer <your_jwt_token>
Accept: application/json
Content-Type: application/json

Get part operations
GET /api/part/{id}/get-operation?term=clean
Headers:
Authorization: Bearer <your_jwt_token>
Accept: application/json
Content-Type: application/json

## Notes

- `GET /api/parts` includes records with `status = 99`.
- `GET /api/part_active` excludes records with `status = 11`.
- `PUT /api/part_setstatus/{id}` only supports current status `1` and `99`.
- `GET` dan `POST` menggunakan `area_id` dari user login.
- `description` may be `null`.
- Validation is required for create and update requests.
