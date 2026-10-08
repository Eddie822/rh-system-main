# Supervisor group requests

## Setup

Apply the additive migration with `sail artisan migrate`.
Existing individual requests remain individual requests; no data conversion is required.

In **Administración → Usuarios → Editar**:

1. Set the supervisor's role to **Supervisor** and assign their area.
2. For each worker, select **Supervisor asignado** and save.

Supervisors can create group requests from **Solicitudes** or **Mis solicitudes → Nueva solicitud grupal**. Only workers currently assigned to the submitting supervisor are selectable and accepted by the server. A supervisor needs an area; that area determines the first approving area manager, including when an assigned worker belongs to another area.

## Workflow

- One request contains one or more employees, each with their own dates and overtime hours.
- Each employee may appear once and have up to seven distinct future dates in the same ISO week and year. Hours must be greater than zero, at most 12 per day, with up to two decimal places. The form accepts up to 100 employees per request.
- The submitting supervisor may edit while the request is pending area-manager approval.
- The entire group follows **Area manager → HR manager → Plant manager**. Each stage approves or rejects the whole request; rejection requires a reason and ends the flow.
- Decisions lock the request and write the audit entry and status in one transaction. Duplicate/stale decisions and edits after the first approval are rejected.
- Included employees see the shared status and approval history through **Mis solicitudes → Ver seguimiento**, with only their own dates and hours. They cannot edit or approve the request. Supervisors and authorized managers see all entries.
- Employee assignment is checked again on submission and edit. Reassigning an employee does not remove their access to requests that already include them. Removing them from a still-pending group does remove that request from their tracking.

## Data model

- `users.supervisor_id` links a worker to their supervisor.
- `requests.is_group` distinguishes grouped and existing individual requests.
- For grouped requests, the existing `requests.employee_id` identifies the submitting supervisor; for individual requests it retains its original meaning.
- `request_employees` links a grouped request to its participants.
- `request_days.employee_id` identifies whose hours each group entry represents. It stays null for existing individual entries.
- A group has one status and approval history, so dashboards count it as one request.

## Verification

Run `sail php vendor/bin/phpunit tests/Feature/GroupRequestTest.php`.
These tests explicitly use SQLite in memory and do not modify the application's database. They cover assignment restrictions, privacy, validation, group editing, approval sequencing, whole-group rejection, stale decisions, transaction rollback, and individual-request compatibility.
