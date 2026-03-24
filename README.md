# Laravel Multi-Tenant / HIPAA-Oriented Compliance Schema

Laravel application demonstrating **tenant isolation**, **encrypted PHI / JSON fields**, **`crp_audit_logs`** for immutable-style service change tracking, and optional **local “S3 path” document** delivery via signed URLs. The database layout follows the **ER compliance reference** (clients as PHI anchor, `service_logs` as core engine, goals, forms, signatures, note versions).

**Stack:** Laravel 13, PHP 8.2+

Full relationship and cascade reference: [docs/ER_DIAGRAM_REFERENCE.md](docs/ER_DIAGRAM_REFERENCE.md).

---

## Implemented database (migrations)

Single migration creates: **`clients`**, **`goals`**, **`form_templates`**, **`client_metadata`**, **`service_logs`**, **`note_versions`**, **`signatures`**, **`form_submissions`**, **`crp_audit_logs`**, with FK rules per the ER spec (CASCADE / RESTRICT / SET NULL where defined).

| Area | Implementation |
|------|----------------|
| **Tenant isolation** | `TenantScope` on models with `crp_id`; `TenantScopeThroughServiceLog` on **`note_versions`** and **`signatures`** (tenant via `service_logs.crp_id`). `App\Support\TenantContext` sets the active tenant. |
| **PHI / sensitive data** | **Clients:** `ssn` (`encrypted`), `dob` (`encrypted:date`). **Client metadata:** `value` encrypted. **Service logs:** `notes_master` as `encrypted:array`. **Note versions / form submissions:** encrypted JSON as specified. **`crp_audit_logs`:** `old_values` / `new_values` as `encrypted:array`; integrity **`hash`** (SHA-256). |
| **Audit logging** | **`ServiceLogObserver`** dispatches **`RecordServiceLogAuditJob`** (queue) to persist **`CrpAuditLog`** rows; **`TenantContext` is restored** after the job so the request tenant is not cleared. |
| **10-day lock** | `ServiceLogLockService::isLocked()` treats logs as read-only when **`locked_at`** is set or after **`SERVICE_LOG_LOCK_DAYS`** (default 10) from the **created** timestamp. **`php artisan service-logs:apply-locks`** stamps **`locked_at`**; scheduled daily in `bootstrap/app.php`. |
| **Duplicate detection** | Same `client_id` + **`narrative_hash`** (SHA-256 of `notes_master`) within lookback hours. Optional **`SERVICE_LOG_ENFORCE_DUPLICATES=true`** rejects saves. |
| **Time conflicts** | Optional **`started_at` / `ended_at`** on **`service_logs`**; **`ServiceLogTimeConflictDetector`** finds overlapping intervals for the same **`staff_id`**. **`SERVICE_LOG_ENFORCE_TIME_CONFLICTS=true`** rejects overlapping saves. |
| **PHI files** | **`PhiDocumentStorageService`** uses **`PHI_DOCUMENTS_DISK`** (`phi_local` or `s3`). Local paths return **file response**; S3 returns a **temporary redirect** to a pre-signed URL. |

---

## Repository layout (main pieces)

```
app/Models/
  Client.php, ClientMetadata.php, Goal.php, ServiceLog.php
  NoteVersion.php, Signature.php, FormTemplate.php, FormSubmission.php
  CrpAuditLog.php, User.php
  Scopes/TenantScope.php, TenantScopeThroughServiceLog.php
app/Services/Compliance/
  ServiceLogLockService.php, ServiceLogDuplicateDetector.php, ServiceLogTimeConflictDetector.php
app/Services/PhiDocumentStorageService.php
app/Jobs/RecordServiceLogAuditJob.php
config/compliance.php
database/migrations/2025_03_24_100000_create_er_compliance_schema.php
database/migrations/2026_01_15_000001_add_scheduling_to_service_logs_table.php
```

---

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # if using SQLite
php artisan migrate
php artisan test
```

---

## Tenancy

Set the tenant before tenant-scoped queries:

```php
use App\Support\TenantContext;

TenantContext::set($crpId);
```

If `TenantContext` is unset, tenant global scopes resolve to **no rows**. Use `Model::withoutGlobalScopes()` only where intentional (e.g. signed document download after resolving the `ServiceLog`).

---

## Production file storage

Set **`PHI_DOCUMENTS_DISK=s3`** and configure **`AWS_*`** in `.env`. Use **`Storage::disk('s3')`** with SSE-KMS, IAM, and short-lived URLs. **`s3_path` / `pdf_s3_key`** remain opaque keys. Local simulation notes are in `config/filesystems.php` above **`phi_local`**.

---

## Compliance environment (optional)

| Variable | Purpose |
|----------|---------|
| `SERVICE_LOG_LOCK_DAYS` | Days before a log is treated as locked (default `10`). |
| `SERVICE_LOG_DUPLICATE_LOOKBACK_HOURS` | Duplicate search window (default `72`). |
| `SERVICE_LOG_ENFORCE_DUPLICATES` | `true`/`false` — reject duplicate narrative hashes. |
| `SERVICE_LOG_ENFORCE_TIME_CONFLICTS` | `true`/`false` — reject overlapping staff intervals. |
| `PHI_DOCUMENTS_DISK` | Laravel disk name (`phi_local` or `s3`). |

---

## License

The Laravel framework is open-sourced under the [MIT license](https://opensource.org/licenses/MIT). This sample app follows the same unless your organization specifies otherwise.
