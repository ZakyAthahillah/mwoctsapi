# Targets API

## Overview

This endpoint group manages MTBF and MTTR target data per part, year, and month using JWT authentication.

## Authentication

Use the header below for all target endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Endpoints

### GET `/api/targets`

Get paginated target summaries grouped by part and year.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `year` optional
- `part_id` optional
- `search` optional, searches part name

### GET `/api/targets/check`

Check whether a target exists for a part and year.

Query parameters:

- `year` required
- `part_id` required

### GET `/api/targets/{part}/{year}`

Get target detail for one part and year.

### POST `/api/targets`

Create or replace target months for one part and year.

Request body:

```json
{
  "year": 2026,
  "part_id": 1,
  "targets": [
    {
      "month": 1,
      "mtbf": 120,
      "mttr": 30
    },
    {
      "month": 2,
      "mtbf": 130,
      "mttr": 28
    }
  ]
}
```

### PUT `/api/targets/{part}/{year}`

Replace target months for one existing part and year.

Request body:

```json
{
  "targets": [
    {
      "month": 1,
      "mtbf": 140,
      "mttr": 25
    }
  ]
}
```

### DELETE `/api/targets/{part}/{year}`

Delete target months for one part and year.

## Notes

- The API reads and writes the existing `target_models` table.
- Create and update replace all target months for the requested part and year.
- Targets are scoped to the authenticated user's `area_id`.
