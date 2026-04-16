# Serial Numbers API

## Overview

This endpoint group manages serial number assignments using JWT authentication. It also supports the initial assignment flow that writes records into `serial_number_logs`.

## Authentication

Use the header below for all serial number endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Route Location

Serial number API routes are defined in `routes/api.php`.

## Endpoints

### GET `/api/serial-numbers`

Get paginated serial number assignment data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- `area_id` optional
- `machine_id` optional
- `position_id` optional
- `part_id` optional

### GET `/api/serial-numbers/{id}`

Get a single serial number assignment detail.

### POST `/api/serial-numbers`

Create a new serial number assignment and write a log with action `1`.

Request body:

```json
{
  "area_id": 1,
  "machine_id": 1,
  "position_id": 1,
  "part_id": 1,
  "part_serial_number_id": 1
}
```

### PUT `/api/serial-numbers/{id}`

Update the linked `part_serial_number_id` and write a log with action `2` if the serial changes.

### GET `/api/serial-numbers/first/{partSerialNumber}`

Get the latest initial assignment data for a part serial number.

### PUT `/api/serial-numbers/first/{partSerialNumber}`

Create or update the initial assignment for a part serial number.

Request body:

```json
{
  "area_id": 1,
  "machine_id": 1,
  "position_id": 1
}
```

## Notes

- List data joins related area, machine, position, part, and part serial number data.
- Duplicate serial number usage in the same area is blocked.
- The same area, machine, position, and part combination can only hold one active serial number assignment.
- Initial assignment writes into `serial_number_logs`.
