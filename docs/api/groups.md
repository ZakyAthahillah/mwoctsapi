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
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/group_active`

Get paginated group data where `status != 11`.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/groups/{id}`

Get a single group detail.

### POST `/api/groups`

Create a new group.

Request body:

```json
{
  "name": "Group A",
  "status": 1
}
```

### PUT `/api/groups/{id}`

Update a group.

### DELETE `/api/groups/{id}`

Delete a group logically by changing `status` to `99`.

### PUT `/api/group_setstatus/{id}`

Toggle group status between `99` and `1`.

## Notes

- Active group queries exclude records with `status = 99`.
- `GET /api/group_active` excludes records with `status = 11`.
- `PUT /api/group_setstatus/{id}` only supports current status `1` and `99`.
- `GET` dan `POST` menggunakan `area_id` dari user login.
- Validation is required for create and update requests.
- Forbidden requests return the standard API error format.
