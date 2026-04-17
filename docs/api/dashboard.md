# Dashboard API

## Overview

This endpoint returns dashboard summary data for the authenticated user, including reporting counts by status.

## Authentication

Use JWT bearer authentication.

## Endpoint

### GET `/api/dashboard`

Get dashboard summary data.

Query parameters:

- `area_id` optional. If omitted, the authenticated user's `area_id` is used when available.

## Notes

- Reporting status counts include `new`, `on_progress`, `extend`, `approval`, and `finish`.
- The response also returns a default 30-day period range for dashboard display.
