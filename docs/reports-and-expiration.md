# Reportes and request deadlines

## Reports

Administrators and HR users (`admin`, `rh`, `hr_manager`) can open **Reportes** in the admin sidebar. Other roles cannot access the preview or download endpoint.

- Only requests with final status `approved` are included, for both individual and grouped requests.
- Filter by overtime day, ISO week/year (Monday–Sunday), or an inclusive date range. Area and employee payroll number are optional filters. The area list is read from the current `areas` database table on each Livewire render; the approvals inbox and user administration also read this table.
- Filters refer to the overtime date, not the submission or approval date. For a group request, payroll filters match the participating worker, not the supervisor who submitted it.
- The Livewire preview and totals update as filters change, without a full page reload, and show 25 rows per page. **Descargar Excel** keeps a normal validated download endpoint and downloads every matching row, one row per employee and overtime date.
- Excel columns include request ID/type, payroll number, employee, request area/group, overtime date, ISO year/week, hours, reason, submitter, and final approval timestamp when present.
- Payroll numbers retain leading zeros; hours are numeric; user text is written literally rather than interpreted as Excel formulas.

## Deadlines and alerts

The request deadline is midnight in `config('app.timezone')`, on the earliest overtime date. For example, overtime starting October 10 has a deadline of October 10 at 00:00; warnings start October 5 at 00:00. A grouped request has one shared deadline based on all its employees' dates.

- Pending requests show **Por vencer** during the five days before the deadline, then **Vencida** at or after the deadline.
- Expiration is advisory: it never changes the approval status and never blocks approval, rejection, or otherwise-permitted editing.
- Approved and rejected requests retain their deadline but do not show pending-expiration alerts.
- Alerts appear in request lists, the approvals inbox, and request details. These screens refresh every 60 seconds while open. The admin dashboard shows counts when loaded; these are in-app alerts, not email notifications.
- Deadline filters isolate pending requests that are nearing their deadline or overdue.
- Saving, updating, or deleting an overtime day through its model recalculates the parent deadline. Bulk SQL changes bypass model events and must call `Request::syncExpiration()` afterward.
- `config/requests.php` configures the advance-warning window. No scheduler or background worker is required for alerts.

The migration `2026_09_29_120000_add_request_expiration.php` adds the deadline column and backfills existing requests from their earliest overtime day without changing statuses. Requests without dates retain a null deadline.

## Validation

```bash
./vendor/bin/sail php vendor/bin/phpunit tests/Feature/ReportsAndExpirationTest.php tests/Feature/GroupRequestTest.php tests/Feature/DatabaseSeederTest.php
npm run build
```

The listed tests explicitly use isolated SQLite in-memory databases.

The follow-up migration `2026_09_29_130000_align_request_deadlines_with_overtime.php` recalculates existing deadlines to the first overtime date. The five-day period is an advance warning only.
