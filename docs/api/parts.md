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
- `area_id` optional

### GET `/api/parts/{id}`

Get a single part detail.

### POST `/api/parts`

Create a new part.

Request body:

```json
{
  "area_id": 1,
  "code": "PRT001",
  "name": "Part A",
  "description": "Deskripsi part",
  "status": 1
}
```

### PUT `/api/parts/{id}`

Update a part.

### DELETE `/api/parts/{id}`

Delete a part logically by changing `status` to `99`.

## Notes

- Active part queries exclude records with `status = 99`.
- `area_id` and `description` may be `null`.
- Validation is required for create and update requests.
