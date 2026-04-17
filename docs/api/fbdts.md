# FBDT API

## Overview

This endpoint group manages FBDT target data per year and month.

## Authentication

Use JWT bearer authentication.

## Endpoints

### GET `/api/fbdts`

Get paginated FBDT year summaries.

### GET `/api/fbdts/check`

Check whether FBDT data already exists for an area and year.

Query parameters:

- `area_id` required
- `year` required

### GET `/api/fbdts/{year}`

Get FBDT detail for a year.

### POST `/api/fbdts`

Create FBDT yearly target data.

### PUT `/api/fbdts/{year}`

Update FBDT yearly target data.

Request body for create and update:

```json
{
  "area_id": 1,
  "year": 2026,
  "targets": [
    {
      "month": 1,
      "fb": 10,
      "dt": 20,
      "mtbf": 30,
      "mttr": 40
    }
  ]
}
```
