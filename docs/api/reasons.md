# Reasons API

## Overview

This endpoint group manages reason data using JWT authentication. Reason deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all reason endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Route Location

Reason API routes are defined in `routes/api.php`.

## Endpoints

### GET `/api/reasons`

Get paginated reason data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- `area_id` optional
- `division_id` optional

### GET `/api/reasons/{id}`

Get a single reason detail.

### POST `/api/reasons`

Create a new reason.

Request body:

```json
{
  "area_id": 1,
  "code": "RSN001",
  "name": "Alasan A",
  "division_id": 1,
  "status": 1
}
```

### PUT `/api/reasons/{id}`

Update a reason.

### DELETE `/api/reasons/{id}`

Delete a reason logically by changing `status` to `99`.

## Notes

- Active reason queries exclude records with `status = 99`.
- `area_id` and `division_id` may be `null`.
- Validation is required for create and update requests.
