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
- `area_id` optional

### GET `/api/divisions/{id}`

Get a single division detail.

### POST `/api/divisions`

Create a new division.

Request body:

```json
{
  "area_id": 1,
  "code": "DIV001",
  "name": "Divisi A",
  "status": 1
}
```

### PUT `/api/divisions/{id}`

Update a division.

### DELETE `/api/divisions/{id}`

Delete a division logically by changing `status` to `99`.

## Notes

- Active division queries exclude records with `status = 99`.
- `area_id` may be `null`.
- Validation is required for create and update requests.
