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
- `area_id` optional

### GET `/api/positions/{id}`

Get a single position detail.

### POST `/api/positions`

Create a new position.

Request body:

```json
{
  "area_id": 1,
  "name": "Posisi A",
  "description": "Deskripsi posisi",
  "status": 1
}
```

### PUT `/api/positions/{id}`

Update a position.

### DELETE `/api/positions/{id}`

Delete a position logically by changing `status` to `99`.

## Notes

- Active position queries exclude records with `status = 99`.
- `area_id` and `description` may be `null`.
- Validation is required for create and update requests.
