# Operations API

## Overview

This endpoint group manages operation data using JWT authentication. Operation deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all operation endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Route Location

Operation API routes are defined in `routes/api.php`.

## Endpoints

### GET `/api/operations`

Get paginated operation data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- `area_id` optional

### GET `/api/operations/{id}`

Get a single operation detail.

### POST `/api/operations`

Create a new operation.

Request body:

```json
{
  "area_id": 1,
  "code": "OPR001",
  "name": "Pekerjaan A",
  "status": 1
}
```

### PUT `/api/operations/{id}`

Update an operation.

### DELETE `/api/operations/{id}`

Delete an operation logically by changing `status` to `99`.

## Notes

- Active operation queries exclude records with `status = 99`.
- `area_id` may be `null`.
- Validation is required for create and update requests.
