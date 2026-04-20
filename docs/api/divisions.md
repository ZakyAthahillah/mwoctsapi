# Divisions API

## Overview

This endpoint group manages division data using JWT authentication. Division deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all division endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Route Location

Division API routes are defined in `routes/api.php`.

## Endpoints

### GET `/api/divisions`

Get paginated division data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/division_active`

Get paginated division data where `status != 11`.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/divisions/{id}`

Get a single division detail.

### POST `/api/divisions`

Create a new division.

Request body:

```json
{
  "code": "DIV001",
  "name": "Divisi A",
  "status": 1
}
```

### PUT `/api/divisions/{id}`

Update a division.

### DELETE `/api/divisions/{id}`

Delete a division logically by changing `status` to `99`.

### PUT `/api/division_setstatus/{id}`

Toggle division status between `99` and `1`.

## Notes

- Active division queries exclude records with `status = 99`.
- `GET /api/division_active` excludes records with `status = 11`.
- `PUT /api/division_setstatus/{id}` only supports current status `1` and `99`.
- `GET` dan `POST` menggunakan `area_id` dari user login.
- Validation is required for create and update requests.
