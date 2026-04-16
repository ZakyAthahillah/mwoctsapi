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
- `area_id` optional
- `group_id` optional

### GET `/api/informants/{id}`

Get a single informant detail.

### POST `/api/informants`

Create a new informant.

Request body:

```json
{
  "area_id": 1,
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

## Notes

- Active informant queries exclude records with `status = 99`.
- `area_id` and `group_id` may be `null`.
- Validation is required for create and update requests.
- Forbidden requests return the standard API error format.
