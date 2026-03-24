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
| **Audit logging** | **`ServiceLogObserver`** writes **`CrpAuditLog`** rows on `ServiceLog` **created** / **updated** (`action_type`, `resource_type` = `service_logs`, `resource_id`, request metadata when available). Observer is a **singleton** in `AppServiceProvider`. |
| **Optional files** | **`signatures.s3_path`** points at objects on the local **`phi_local`** disk (production: real S3). **Signed route** `phi.service-log-document` resolves the first matching signature file for a service log. |

---

## Repository layout (main pieces)

```
app/Models/
  Client.php, ClientMetadata.php, Goal.php, ServiceLog.php
  NoteVersion.php, Signature.php, FormTemplate.php, FormSubmission.php
  CrpAuditLog.php, User.php
  Scopes/TenantScope.php, TenantScopeThroughServiceLog.php
database/migrations/2025_03_24_100000_create_er_compliance_schema.php
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

Use **`Storage::disk('s3')`** (or equivalent) with encryption, IAM, and **short-lived signed URLs**; keep **`s3_path` / `pdf_s3_key`** as object keys. Notes are in `config/filesystems.php` above **`phi_local`**.

---

## License

The Laravel framework is open-sourced under the [MIT license](https://opensource.org/licenses/MIT). This sample app follows the same unless your organization specifies otherwise.
