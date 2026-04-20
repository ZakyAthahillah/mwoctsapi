# Informants API

## Overview

This endpoint group manages informant data using JWT authentication. Informant deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all informant endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Endpoints

### GET `/api/informants`

Get paginated informant data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login
- `group_id` optional

### GET `/api/informant_active`

Get paginated informant data where `status != 11`.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login
- `group_id` optional

### GET `/api/informants/{id}`

Get a single informant detail.

### POST `/api/informants`

Create a new informant.

Request body:

```json
{
  "code": "INF001",
  "name": "Pelapor A",
  "status": 1,
  "group_id": 5
}
```

### PUT `/api/informants/{id}`

Update an informant.

### DELETE `/api/informants/{id}`

Delete an informant logically by changing `status` to `99`.

### PUT `/api/informant_setstatus/{id}`

Toggle informant status between `99` and `1`.

## Notes

- Active informant queries exclude records with `status = 99`.
- `GET /api/informant_active` excludes records with `status = 11`.
- `PUT /api/informant_setstatus/{id}` only supports current status `1` and `99`.
- `GET` dan `POST` menggunakan `area_id` dari user login.
- `group_id` may be `null`.
- Validation is required for create and update requests.
- Forbidden requests return the standard API error format.
