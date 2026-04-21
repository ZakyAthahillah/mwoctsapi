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

Get paginated machine data. This list only returns machines with status `1` and `99`.

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

The detail response includes assigned machine positions as parallel arrays:

```json
{
  "position_id": ["1", "2"],
  "position_name": ["Posisi A", "Posisi B"]
}
```

### GET `/api/machines/full-data-array`

Get machine option list for selector/search use cases and legacy machine lookups.

Query parameters:

- `term` optional, filter by code, name, or description

### GET `/api/machines/full-data-array-job`

Get active machine option list for job forms. Only machines with `status = 1` are returned.

Query parameters:

- `term` optional, filter by code, name, or description

### GET `/api/machines/{id}/detail`

Get a single machine detail with mapped front and side parts. This response also includes `position_id` and `position_name` arrays.

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
Endpoint ini juga mendukung assign beberapa posisi sekaligus saat create memakai field `position_id`.

Contoh JSON:

```json
{
  "code": "MCH001",
  "name": "Mesin Potong",
  "description": "Mesin untuk proses potong",
  "image": null,
  "image_side": null,
  "position_id": [1, 2],
  "status": 1
}
```

Jika `image` dan `image_side` dikirim sebagai nama file string saat create, nilai yang disimpan akan otomatis menjadi `machines/{id}/front.png` dan `machines/{id}/side.png`.
Jika `image`, `image_side`, atau `status` tidak dikirim saat create, API akan mengisi `image=null`, `image_side=null`, dan `status=1`.

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
position_id[]=1
position_id[]=2
status=1
```

### PUT `/api/machines/{id}`

Update a machine.

This endpoint can also replace mapped EasyPIN part points for front and side machine images.

Example JSON:

```json
{
  "area_id": 1,
  "code": "MCH001",
  "name": "Mesin Potong Update",
  "description": "Mesin update",
  "image": "machines/10075/front.png",
  "image_side": "machines/10075/side.png",
  "status": 1,
  "parts": {
    "id": [1, 2],
    "x": [12.4, 55.2],
    "y": [22.1, 66.7],
    "x_side": [10.4, 51.2],
    "y_side": [20.1, 61.7]
  }
}
```

When `parts.id` is provided, existing records in `machine_parts` and `machine_part_sides` for the machine are replaced. The coordinate arrays must contain the same number of items as `parts.id`.

If the client sends `multipart/form-data` or cannot send nested JSON, `parts` may be sent as a JSON string:

```text
parts={"id":[1,2],"x":[12.4,55.2],"y":[22.1,66.7],"x_side":[10.4,51.2],"y_side":[20.1,61.7]}
```

### DELETE `/api/machines/{id}`

Delete a machine logically by changing `status` to `99`.

### PUT `/api/machine_setstatus/{id}`

Toggle machine status between `99` and `1`.

## Notes

- `GET /api/machines` returns machines with status `1` and `99`.
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
- `status` pada create akan default ke `1` jika tidak dikirim.
- `position_id` optional dan bisa berisi 1 atau lebih `position.id` aktif dari area user yang login.
- Saat `image` atau `image_side` dikirim sebagai file upload pada endpoint create, file disimpan ke `public/machines/{id}`.
- Nilai kolom `image` dan `image_side` akan disimpan sebagai path relatif, misalnya `machines/{id}/front.jpeg`.
- Untuk menampilkan di web, gabungkan dengan base URL aplikasi, misalnya `{{ url($machine->image) }}` di Blade atau `window.location.origin + '/' + machine.image` di frontend.
- `GET /api/machines/{id}/detail` includes `parts` for front image pins and `parts_side` for side image pins.
- Machine responses include `area_name` when the related area exists.
- Machine option endpoints follow the standardized API response format even for legacy-compatible routes.
- Validation is required for create and update requests.
- Forbidden requests return the standard API error format.
