# Auth and User Management API

## Overview

This API uses JWT bearer authentication for central API access across web, mobile, and other clients.

## Authentication

Use the header below for protected endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Endpoints

### POST `/api/register`

Register a new user. New users are created with `is_admin = false`.

Request body:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

Success response:

```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": "1",
      "name": "John Doe",
      "email": "john@example.com",
      "is_admin": false,
      "created_at": "2026-04-15 15:00:00",
      "updated_at": "2026-04-15 15:00:00"
    },
    "authorization": {
      "type": "bearer",
      "token": "<jwt-token>",
      "expires_in_minutes": 60
    }
  },
  "meta": null,
  "errors": null
}
```

### POST `/api/login`

Authenticate a user and return a JWT token.

Request body:

```json
{
  "email": "john@example.com",
  "password": "secret123"
}
```

### POST `/api/logout`

Invalidate the current JWT token.

Protected endpoint: `auth:api`

### POST `/api/refresh`

Refresh the current JWT token and return a new bearer token.

Header:

```http
Authorization: Bearer <old-token>
```

Success response:

```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "user": {
      "id": "1",
      "name": "John Doe",
      "email": "john@example.com",
      "is_admin": false,
      "created_at": "2026-04-15 15:00:00",
      "updated_at": "2026-04-15 15:00:00"
    },
    "authorization": {
      "type": "bearer",
      "token": "<new-jwt-token>",
      "expires_in_minutes": 60,
      "refresh_expires_in_minutes": 20160
    }
  },
  "meta": null,
  "errors": null
}
```

Notes:

- Setelah refresh, client harus menyimpan dan memakai token baru untuk request berikutnya.
- Jika token tidak dikirim atau sudah tidak bisa di-refresh, API akan mengembalikan `401 Unauthorized`.

### PUT `/api/users/{id}`

Update a user. This endpoint is restricted to admin users.

Protected endpoint: `auth:api`, `admin`

Request body:

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123",
  "is_admin": true
}
```

Notes:

- `password` is optional.
- `is_admin` is required.

### DELETE `/api/users/{id}`

Delete a user. This endpoint is restricted to admin users.

Protected endpoint: `auth:api`, `admin`

Notes:

- Admin cannot delete their own account using this endpoint.

## Standard Error Response

Validation error example:

```json
{
  "success": false,
  "message": "Bad request",
  "data": null,
  "meta": null,
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

Unauthorized example:

```json
{
  "success": false,
  "message": "Unauthorized",
  "data": null,
  "meta": null,
  "errors": {
    "auth": [
      "Token not provided"
    ]
  }
}
```
