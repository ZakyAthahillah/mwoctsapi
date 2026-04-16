# Technicians API

## Overview

This endpoint group manages technician data using JWT authentication. Technician deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all technician endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Route Location

Technician API routes are defined in `routes/api.php`.

## Endpoints

### GET `/api/technicians`

Get paginated technician data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- `area_id` optional
- `division_id` optional
- `group_id` optional

### GET `/api/technicians/{id}`

Get a single technician detail.

### POST `/api/technicians`

Create a new technician.

Request body:

```json
{
  "area_id": 1,
  "code": "TCN001",
  "name": "Teknisi A",
  "division_id": 1,
  "status": 1,
  "group_id": null
}
```

### PUT `/api/technicians/{id}`

Update a technician.

### DELETE `/api/technicians/{id}`

Delete a technician logically by changing `status` to `99`.

## Notes

- Active technician queries exclude records with `status = 99`.
- `area_id`, `division_id`, and `group_id` may be `null`.
- Validation is required for create and update requests.
- Forbidden requests return the standard API error format.
