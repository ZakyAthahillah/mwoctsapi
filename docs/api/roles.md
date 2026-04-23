# Roles API

## Overview

This endpoint group manages user roles using JWT authentication and admin middleware. Role names are unique. When the authenticated admin has an `area_id`, newly created role names are prefixed with that area id.

## Authentication

Use the header below for all role endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Endpoints

### GET `/api/roles`

Get paginated roles.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- `area_id` optional, available only for admins without an authenticated area scope

### GET `/api/roles/{id}`

Get a single role detail.

### POST `/api/roles`

Create a role.

Request body:

```json
{
  "name": "maintenance-admin",
  "display_name": "Maintenance Admin",
  "guard_name": "api",
  "area_id": 1
}
```

### PUT `/api/roles/{id}`

Update role display data.

Request body:

```json
{
  "display_name": "Maintenance Supervisor",
  "guard_name": "api",
  "area_id": 1
}
```

### DELETE `/api/roles/{id}`

Delete a role.

### GET `/api/roles/{id}/permissions`

Get permission options and currently selected permissions for a role.

### PUT `/api/roles/{id}/permissions`

Replace role permissions.

Request body:

```json
{
  "permission_ids": [1, 2, 3]
}
```

## Notes

- These endpoints require admin access.
- Role permission assignment uses the `role_has_permissions` table when available.
