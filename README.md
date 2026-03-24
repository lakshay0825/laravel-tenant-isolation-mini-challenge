# Laravel Multi-Tenant / HIPAA Compliance Mini-Challenge

Sample Laravel application demonstrating **tenant isolation**, **PHI field encryption**, **audit logging** for `ServiceLog` changes, and optional **local document storage** with time-limited signed URLs. Built for a scoped technical assessment (multi-tenancy and compliance-oriented patterns).

**Stack:** Laravel 13, PHP 8.2+

For a **full product ER reference** (clients, `service_logs`, goals, forms, `crp_audit_logs`, cascade rules, compliance mapping), see [docs/ER_DIAGRAM_REFERENCE.md](docs/ER_DIAGRAM_REFERENCE.md).

---

## Challenge scope (deliverables)

| Area | Implementation |
|------|----------------|
| **Tenant isolation** | `crp_id` on `Client` and `ServiceLog`; `TenantScope` global scope; `App\Support\TenantContext` sets the active tenant for the request or test. |
| **PHI protection** | `Client`: `ssn` → `encrypted` cast, `dob` → `encrypted:date` cast; UUID primary keys; indexed `crp_id` on tenant-scoped tables. |
| **Audit logging** | `ServiceLogObserver` on create/update; persisted in `audit_logs` (`old_values` / `new_values` JSON). |
| **Optional files** | `phi_local` disk under `storage/app/phi`; `GET` route `phi.service-log-document` uses a **signed temporary URL** to download an attached document. |

Supporting model: **`AuditLog`** (required to store audits; not a third “business” entity beyond the brief’s storage requirement).

---

## Repository layout (relevant parts)

```
app/
  Http/Controllers/ServicePhiDocumentController.php  # Signed URL document download
  Models/
    AuditLog.php, Client.php, ServiceLog.php
    Scopes/TenantScope.php
  Observers/ServiceLogObserver.php
  Support/TenantContext.php
database/migrations/
  *_create_clients_table.php
  *_create_service_logs_table.php
  *_create_audit_logs_table.php
tests/Feature/
  TenantIsolationAndPhiEncryptionTest.php
  ServiceLogAuditLoggingTest.php
  PhiDocumentTemporaryUrlTest.php          # Optional
```

---

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure DB in .env (SQLite default in Laravel skeleton: database/database.sqlite)
touch database/database.sqlite   # if using SQLite and file does not exist
php artisan migrate
```

Run the automated tests:

```bash
php artisan test
```

PHPUnit uses an in-memory SQLite database (`phpunit.xml`); no separate test DB configuration is required for the feature tests.

---

## How tenancy works

1. Set the current tenant before running queries or creating tenant-owned rows:

   ```php
   use App\Support\TenantContext;

   TenantContext::set($crpId); // e.g. from auth / subdomain / header
   ```

2. **Eloquent** queries on `Client`, `ServiceLog`, and `AuditLog` automatically apply `TenantScope` and filter by `crp_id`.

3. If `TenantContext` is not set, the scope resolves to **no rows** (`whereRaw('1 = 0')`) to avoid accidental cross-tenant reads.

4. Admin or system jobs that must bypass the scope should use `Model::withoutGlobalScopes()` deliberately (see the document controller for `ServiceLog`).

---

## Production file storage (brief)

This project uses a **local** disk (`phi_local`) for simulation. In production you would typically:

- Store blobs on **Amazon S3** (or compatible object storage) with **encryption at rest** (e.g. SSE-KMS).
- Upload via `Storage::disk('s3')->put()` (streaming for large PDFs).
- Issue **short-lived pre-signed URLs** (or CloudFront signed URLs) instead of exposing bucket keys; enforce auth and tenant checks **before** generating the URL.
- Apply bucket policies, VPC endpoints, logging, and lifecycle rules per your security and retention policy.

Details are also noted inline in `config/filesystems.php` above the `phi_local` disk definition.

---

## Observer note

`ServiceLogObserver` is registered as a **singleton** in `AppServiceProvider` so state captured during `saving` is available when `updated` runs (Laravel’s container would otherwise resolve a new observer instance per event).

---

## License

The Laravel framework is open-sourced under the [MIT license](https://opensource.org/licenses/MIT). This sample app follows the same unless your organization specifies otherwise.
