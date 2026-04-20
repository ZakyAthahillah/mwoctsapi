# Positions API

## Overview

This endpoint group manages position data using JWT authentication. Position deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all position endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Route Location

Position API routes are defined in `routes/api.php`.

## Endpoints

### GET `/api/positions`

Get paginated position data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/position_active`

Get paginated position data where `status != 11`.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/positions/{id}`

Get a single position detail.

### POST `/api/positions`

Create a new position.

Request body:

```json
{
  "name": "Posisi A",
  "description": "Deskripsi posisi",
  "status": 1
}
```

### PUT `/api/positions/{id}`

Update a position.

### DELETE `/api/positions/{id}`

Delete a position logically by changing `status` to `99`.

### PUT `/api/position_setstatus/{id}`

Toggle position status between `99` and `1`.

## Notes

- Active position queries exclude records with `status = 99`.
- `GET /api/position_active` excludes records with `status = 11`.
- `PUT /api/position_setstatus/{id}` only supports current status `1` and `99`.
- `GET` dan `POST` menggunakan `area_id` dari user login.
- `description` may be `null`.
- Validation is required for create and update requests.
