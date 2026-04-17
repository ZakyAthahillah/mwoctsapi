# Permissions API

## Overview

Endpoint ini untuk CRUD permission berbasis JSON.

## Authentication

Gunakan JWT bearer token dan akun admin.

## Endpoints

### GET `/api/permissions`

List permission dengan pagination.

Query parameters:

- `per_page` optional
- `search` optional

### GET `/api/permissions/{permission}`

Detail satu permission.

### POST `/api/permissions`

```json
{
  "name": "jobs-index",
  "guard_name": "api"
}
```

### PUT `/api/permissions/{permission}`

```json
{
  "name": "jobs-manage",
  "guard_name": "api"
}
```

### DELETE `/api/permissions/{permission}`

Hapus permission.
