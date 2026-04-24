# Job API Test Schema Fixtures

## Issue

`php artisan test --filter=JobApiTest` failed with SQLite `NOT NULL constraint failed` errors for `reportings.sort_order` and required foreign key columns such as `machine_id`.

## Cause

`RefreshDatabase` runs the real migration for `reportings`, so the fallback schema created inside `JobApiTest::setUp()` is not used when migrations already create the table. Test inserts must include all required columns from `database/migrations/2026_04_16_121750_create_reportings_table.php`.

## Fix

When inserting reportings directly in tests, include the required reporting fields:

- `area_id`
- `reporting_number`
- `machine_id`
- `position_id`
- `part_id`
- `division_id`
- `operation_id`
- `reason_id`
- `informant_id`
- `shift_id_reporting`
- `reporting_date`
- `sort_order`
- `status`
