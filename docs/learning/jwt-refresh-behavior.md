# JWT Refresh Behavior Learning

## Date

2026-04-15

## Context

While validating the JWT refresh endpoint, the initial assumption was that the previous token would always be rejected immediately after `POST /api/refresh`.

## Finding

In the current package setup with `php-open-source-saver/jwt-auth`, the safest client behavior is to immediately replace the old token with the refreshed token. The package refresh flow does issue a new token and uses blacklist logic internally, but the runtime behavior observed during testing did not support documenting a strict "old token is always rejected immediately" guarantee for our current app-level flow.

## Decision

- Keep the refresh endpoint.
- Document the client contract as: after refresh, always store and use the new token for subsequent requests.
- Keep logout as the explicit token invalidation endpoint already covered by feature tests.

## Impact

- API documentation now reflects the observed behavior more accurately.
- Postman and mobile clients should always overwrite the stored bearer token after refresh.
