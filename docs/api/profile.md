# Profile API

## Overview

This endpoint group manages the authenticated user's own profile using JWT authentication.

## Authentication

Use the header below for all profile endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Endpoints

### GET `/api/profile`

Get the authenticated user's profile.

### POST `/api/profile`

Update the authenticated user's profile.

Request body:

```json
{
  "name": "Updated User",
  "email": "updated@example.com",
  "username": "updateduser",
  "image": "images/users/1/profile.png",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

For multipart requests, `image` may be sent as an uploaded file.

## Notes

- `name`, `email`, and `username` are required for update.
- `email` and `username` must be unique, excluding the authenticated user.
- `password` is optional, but must be confirmed when provided.
- `image` is optional and can be `null`, a string path, or an uploaded file.
