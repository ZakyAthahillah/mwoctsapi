# Reporting Reports API

## Overview

This endpoint group provides paginated reporting report data for the authenticated user's area. It is read-only and returns standardized JSON responses.

## Authentication

Use the header below for all reporting report endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Endpoints

### GET `/api/reporting-reports`

Get paginated reporting report data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- `period_start` optional date, defaults to 30 days before current server date
- `period_end` optional date, defaults to current server date
- `division_id` optional
- `machine_id` optional
- `position_id` optional
- `part_id` optional
- `part_serial_number_id` optional
- `operation_id` optional
- `operation_id_actual` optional
- `reason_id` optional
- `informant_id` optional
- `technician_id` optional
- `group_id` optional
- `reporting_type` optional
- `status` optional

Example:

```http
GET /api/reporting-reports?per_page=10&period_start=2026-04-01&period_end=2026-04-30&machine_id=1&status=5
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

### GET `/api/reporting-reports/statuses`

Get reporting report status options.

Example:

```http
GET /api/reporting-reports/statuses
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Notes

- Deleted reportings with `status = 99` are excluded from the list endpoint.
- The list endpoint is scoped to the authenticated user's `area_id`.
- Technician names are loaded in batch to avoid N+1 queries.
