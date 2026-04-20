# Dashboard API

## Overview

This endpoint returns dashboard summary data for the authenticated user, including reporting counts by status.

## Authentication

Use JWT bearer authentication.

## Endpoint

### GET `/api/dashboard`

Get dashboard summary data.

## Notes

- Reporting status counts include `new`, `on_progress`, `extend`, `approval`, and `finish`.
- The response also returns a default 30-day period range for dashboard display.
- Summary dibatasi otomatis berdasarkan `area_id` user yang login.
