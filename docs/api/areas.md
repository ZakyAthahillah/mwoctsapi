# Areas API

## Overview

This endpoint group manages area data using JWT authentication. Area deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all area endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Endpoints

### GET `/api/areas`

Get paginated area data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional

Success response:

```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": [
    {
      "id": "1",
      "code": "AREA01",
      "name": "Jakarta Barat",
      "object_name": "Objek Area",
      "status": 1,
      "created_at": "2026-04-15 16:00:00",
      "updated_at": "2026-04-15 16:00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1
  },
  "errors": null
}
```

### GET `/api/areas/{id}`

Get a single area detail.

### POST `/api/areas`

Create a new area. Admin only.

Request body:

```json
{
  "code": "AREA01",
  "name": "Jakarta Barat",
  "object_name": "Objek Area",
  "status": 1
}
```

### PUT `/api/areas/{id}`

Update an area. Admin only.

Request body:

```json
{
  "code": "AREA02",
  "name": "Jakarta Selatan",
  "object_name": "Objek Area Update",
  "status": 1
}
```

### DELETE `/api/areas/{id}`

Delete an area logically by changing `status` to `99`. Admin only.

## Notes

- Active area queries exclude records with `status = 99`.
- Validation is required for create and update requests.
- Forbidden requests return the standard API error format.
