# EnkiFlow API

This document describes the HTTP API for EnkiFlow.

- **Base URL:** `https://api.enkiflow.com/v1`
- **Authentication:** JSON Web Tokens. Include the token in the `Authorization` header as `Bearer <token>`.
- **Error model:**

```json
{
  "code": "string",
  "message": "string",
  "errors": {
    "field": ["error message"]
  }
}
```

- **Rate limiting:** 120 requests per minute per user. When exceeded the API returns `429 Too Many Requests` with a `Retry-After` header.

## Endpoints

| Method | Path | Summary |
| ------ | ---- | ------- |
| `GET` | `/users/me` | Current user profile |
| `POST` | `/auth/refresh` | Refresh JWT token |
| `GET` | `/boards` | List boards |
| `POST` | `/boards` | Create board |
| `GET` | `/boards/{id}` | Retrieve a board |
| `PATCH` | `/boards/{id}` | Update a board |
| `DELETE` | `/boards/{id}` | Delete a board |
| `GET` | `/tasks` | List tasks |
| `POST` | `/tasks` | Create task |
| `GET` | `/tasks/{id}` | Retrieve a task |
| `PATCH` | `/tasks/{id}` | Update a task |
| `DELETE` | `/tasks/{id}` | Delete a task |
| `POST` | `/tasks/{id}/move` | Move a task to a new board/position |
| `POST` | `/webhooks/test` | Send a test event to your webhooks |

---

## GET `/users/me`

Returns information about the authenticated user.

<details>
<summary>Response</summary>

```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "created_at": "2024-01-01T00:00:00Z"
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `id` | integer | User identifier |
| `name` | string | Full name |
| `email` | string | Email address |
| `created_at` | string | ISO‑8601 creation timestamp |

</details>

---

## POST `/auth/refresh`

Refresh an expired token. The call must include the current refresh token (usually via cookie or request body depending on your client implementation).

<details>
<summary>Request</summary>

```json
{
  "refresh_token": "<refresh-token>"
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `refresh_token` | string | The refresh token issued during login |

</details>

<details>
<summary>Response</summary>

```json
{
  "token": "<jwt>",
  "expires_in": 3600
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `token` | string | New JWT token |
| `expires_in` | integer | Token lifetime in seconds |

</details>

---

## Boards

### `GET /boards`

List the boards the user has access to.

<details>
<summary>Response</summary>

```json
[
  {
    "id": 1,
    "name": "Product",
    "description": "Product backlog"
  }
]
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `id` | integer | Board identifier |
| `name` | string | Board name |
| `description` | string | Optional description |

</details>

### `POST /boards`

Create a new board.

<details>
<summary>Request</summary>

```json
{
  "name": "Product",
  "description": "Product backlog"
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `name` | string | Name of the board |
| `description` | string | Optional description |

</details>

<details>
<summary>Response</summary>

```json
{
  "id": 1,
  "name": "Product",
  "description": "Product backlog",
  "created_at": "2024-01-01T00:00:00Z"
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `id` | integer | Board identifier |
| `name` | string | Board name |
| `description` | string | Optional description |
| `created_at` | string | ISO‑8601 creation timestamp |

</details>

### `GET /boards/{id}`

Retrieve a board by its id.

<details>
<summary>Response</summary>

```json
{
  "id": 1,
  "name": "Product",
  "description": "Product backlog",
  "created_at": "2024-01-01T00:00:00Z"
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `id` | integer | Board identifier |
| `name` | string | Board name |
| `description` | string | Optional description |
| `created_at` | string | ISO‑8601 creation timestamp |

</details>

### `PATCH /boards/{id}`

Update a board.

<details>
<summary>Request</summary>

```json
{
  "name": "Product",
  "description": "Updated description"
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `name` | string | Name of the board |
| `description` | string | Optional description |

</details>

<details>
<summary>Response</summary>

```json
{
  "id": 1,
  "name": "Product",
  "description": "Updated description",
  "updated_at": "2024-01-02T00:00:00Z"
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `id` | integer | Board identifier |
| `name` | string | Board name |
| `description` | string | Optional description |
| `updated_at` | string | ISO‑8601 update timestamp |

</details>

### `DELETE /boards/{id}`

Delete a board by its id.

<details>
<summary>Response</summary>

```json
{
  "deleted": true
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `deleted` | boolean | Indicates deletion success |

</details>

---

## Tasks

### `GET /tasks`

List tasks.

<details>
<summary>Response</summary>

```json
[
  {
    "id": 5,
    "title": "Design homepage",
    "status": "pending",
    "board_id": 1
  }
]
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `id` | integer | Task identifier |
| `title` | string | Task title |
| `status` | string | Task status |
| `board_id` | integer | Associated board |

</details>

### `POST /tasks`

Create a task.

<details>
<summary>Request</summary>

```json
{
  "title": "Design homepage",
  "board_id": 1
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `title` | string | Task title |
| `board_id` | integer | Board where the task belongs |

</details>

<details>
<summary>Response</summary>

```json
{
  "id": 5,
  "title": "Design homepage",
  "status": "pending",
  "board_id": 1,
  "created_at": "2024-01-01T00:00:00Z"
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `id` | integer | Task identifier |
| `title` | string | Task title |
| `status` | string | Task status |
| `board_id` | integer | Board where the task belongs |
| `created_at` | string | ISO‑8601 creation timestamp |

</details>

### `GET /tasks/{id}`

Retrieve a single task.

<details>
<summary>Response</summary>

```json
{
  "id": 5,
  "title": "Design homepage",
  "status": "pending",
  "board_id": 1,
  "created_at": "2024-01-01T00:00:00Z"
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `id` | integer | Task identifier |
| `title` | string | Task title |
| `status` | string | Task status |
| `board_id` | integer | Board identifier |
| `created_at` | string | ISO‑8601 creation timestamp |

</details>

### `PATCH /tasks/{id}`

Update a task.

<details>
<summary>Request</summary>

```json
{
  "title": "Design homepage",
  "status": "in_progress"
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `title` | string | Task title |
| `status` | string | New status |

</details>

<details>
<summary>Response</summary>

```json
{
  "id": 5,
  "title": "Design homepage",
  "status": "in_progress",
  "board_id": 1,
  "updated_at": "2024-01-02T00:00:00Z"
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `id` | integer | Task identifier |
| `title` | string | Task title |
| `status` | string | Updated status |
| `board_id` | integer | Board identifier |
| `updated_at` | string | ISO‑8601 update timestamp |

</details>

### `DELETE /tasks/{id}`

Remove a task.

<details>
<summary>Response</summary>

```json
{
  "deleted": true
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `deleted` | boolean | Indicates deletion success |

</details>

### `POST /tasks/{id}/move`

Move a task to another board or position.

<details>
<summary>Request</summary>

```json
{
  "board_id": 2,
  "position": 3
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `board_id` | integer | Destination board |
| `position` | integer | Position index in the board |

</details>

<details>
<summary>Response</summary>

```json
{
  "id": 5,
  "board_id": 2,
  "position": 3
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `id` | integer | Task identifier |
| `board_id` | integer | Destination board |
| `position` | integer | New position |

</details>

---

## POST `/webhooks/test`

Trigger a test event to all configured webhook URLs for the current user or organization.

<details>
<summary>Request</summary>

```json
{
  "payload": {
    "message": "Test"
  }
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `payload` | object | Arbitrary JSON payload delivered to webhook consumers |
| `payload.message` | string | Example message |

</details>

<details>
<summary>Response</summary>

```json
{
  "sent": true
}
```

| Field | Type | Description |
| ----- | ---- | ----------- |
| `sent` | boolean | Indicates the test event was dispatched |

</details>

---

All endpoints return an error object on failure using the structure defined above.

