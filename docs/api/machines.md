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
- `area_id` optional

### GET `/api/machines/{id}`

Get a single machine detail.

### POST `/api/machines`

Create a new machine.

Request body bisa memakai JSON string URL lama atau multipart form-data untuk upload file.

Contoh JSON:

```json
{
  "area_id": 1,
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

area_id=1
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

## Notes

- Active machine queries exclude records with `status = 99`.
- `area_id`, `description`, `image`, and `image_side` may be `null`.
- Saat `image` atau `image_side` dikirim sebagai file upload pada endpoint create, file disimpan ke `public/images/machines/{id}`.
- Nilai kolom `image` dan `image_side` akan disimpan sebagai path relatif, misalnya `images/machines/{id}/front.jpeg`.
- Untuk menampilkan di web, gabungkan dengan base URL aplikasi, misalnya `{{ url($machine->image) }}` di Blade atau `window.location.origin + '/' + machine.image` di frontend.
- Machine responses include `area_name` when the related area exists.
- Validation is required for create and update requests.
- Forbidden requests return the standard API error format.
