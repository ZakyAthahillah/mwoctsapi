# Machines API

## Overview

This endpoint group manages machine data using JWT authentication. Machine deletion uses a status flag where `99` means deleted.

## Authentication

Use the header below for all machine endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Route Location

Machine API routes are defined in `routes/api.php`.

## Endpoints

### GET `/api/machines`

Get paginated machine data.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/machine_active`

Get paginated machine data where `status != 11`.

Query parameters:

- `per_page` optional, default `10`, max `100`
- `search` optional
- area dibatasi otomatis berdasarkan `area_id` user yang login

### GET `/api/machines/{id}`

Get a single machine detail.

### GET `/api/machines/full-data-array`

Get machine option list for selector/search use cases and legacy machine lookups.

Query parameters:

- `term` optional, filter by code, name, or description

### GET `/api/machines/full-data-array-job`

Get active machine option list for job forms. Only machines with `status = 1` are returned.

Query parameters:

- `term` optional, filter by code, name, or description

### GET `/api/machines/{id}/detail`

Get a single machine detail with mapped front parts.

### GET `/api/machines/{id}/positions`

Get machine positions assigned through `machine_position`.

Query parameters:

- `term` optional, filter position name
- `selected` optional, mark a returned item as selected

### GET `/api/machines/{machineId}/positions/{positionId}/detail-job`

Get active machine detail for job usage, including front parts, side parts, and serial number mapping for the requested position.

### GET `/api/machines/{machineId}/positions/{positionId}/parts`

Get machine parts for a single position, including serial number text when available.

### PUT `/api/machines/{id}/activate`

Toggle machine activation between `0` and `1`. The machine must not be deleted and its progress must be `100`.

### POST `/api/machines`

Create a new machine.

Request body bisa memakai JSON string URL lama atau multipart form-data untuk upload file.

Contoh JSON:

```json
{
  "code": "MCH001",
  "name": "Mesin Potong",
  "description": "Mesin untuk proses potong",
  "image": "front.png",
  "image_side": "side.png",
  "status": 1
}
```

Jika `image` dan `image_side` dikirim sebagai nama file string saat create, nilai yang disimpan akan otomatis menjadi `images/machines/{id}/front.png` dan `images/machines/{id}/side.png`.

Contoh multipart form-data:

```text
POST /api/machines
Authorization: Bearer <token>
Accept: application/json
Content-Type: multipart/form-data

code=MCH001
name=Mesin Potong
description=Mesin untuk proses potong
image=<front-image-file>
image_side=<side-image-file>
status=1
```

### PUT `/api/machines/{id}`

Update a machine.

### DELETE `/api/machines/{id}`

Delete a machine logically by changing `status` to `99`.

### PUT `/api/machine_setstatus/{id}`

Toggle machine status between `99` and `1`.

## Notes

- Active machine queries exclude records with `status = 99`.
- `GET /api/machine_active` excludes records with `status = 11`.
- `PUT /api/machine_setstatus/{id}` only supports current status `1` and `99`.
- Legacy compatibility aliases are also available:
  - `GET|POST /api/machine/get-full-data-array`
  - `GET|POST /api/machine/get-full-data-array-job`
  - `GET /api/machine/{machine}/get-detail`
  - `GET /api/machine/{machine}/get-position`
  - `GET|POST /api/machine/{machineId}/{positionId}/get-part`
  - `GET /api/machine/{machineId}/{positionId}/get-detail-job`
  - `PUT|POST /api/machine/{machine}/activate`
- `GET` dan `POST` menggunakan `area_id` dari user login.
- `description`, `image`, and `image_side` may be `null`.
- Saat `image` atau `image_side` dikirim sebagai file upload pada endpoint create, file disimpan ke `public/images/machines/{id}`.
- Nilai kolom `image` dan `image_side` akan disimpan sebagai path relatif, misalnya `images/machines/{id}/front.jpeg`.
- Untuk menampilkan di web, gabungkan dengan base URL aplikasi, misalnya `{{ url($machine->image) }}` di Blade atau `window.location.origin + '/' + machine.image` di frontend.
- Machine responses include `area_name` when the related area exists.
- Machine option endpoints follow the standardized API response format even for legacy-compatible routes.
- Validation is required for create and update requests.
- Forbidden requests return the standard API error format.
