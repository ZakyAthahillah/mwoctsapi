# Downtimes API

## Overview

This endpoint group returns finished downtime reporting data with filters, pagination, and summary totals.

## Authentication

Use JWT bearer authentication.

## Endpoint

### GET `/api/downtimes`

Get paginated downtime report data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `area_id` optional
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
- `period_start` optional, date format
- `period_end` optional, date format

## Notes

- Only records with reporting `status = 5` are included.
- Response metadata includes total downtime duration summaries.
