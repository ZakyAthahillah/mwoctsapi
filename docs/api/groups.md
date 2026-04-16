# Groups API

## Overview

This endpoint group manages group data using JWT authentication. Group deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all group endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Route Location

Group API routes are defined in `routes/api.php`.

## Endpoints

### GET `/api/groups`

Get paginated group data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- `area_id` optional

### GET `/api/groups/{id}`

Get a single group detail.

### POST `/api/groups`

Create a new group.

Request body:

```json
{
  "area_id": 1,
  "name": "Group A",
  "status": 1
}
```

### PUT `/api/groups/{id}`

Update a group.

### DELETE `/api/groups/{id}`

Delete a group logically by changing `status` to `99`.

## Notes

- Active group queries exclude records with `status = 99`.
- `area_id` may be `null`.
- Validation is required for create and update requests.
- Forbidden requests return the standard API error format.
