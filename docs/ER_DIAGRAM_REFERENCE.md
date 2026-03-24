# ER Diagram Reference

Compliance-oriented schema: **clients** as PHI anchor, **service_logs** as core engine, supporting metadata, goals, notes, signatures, forms, and high-performance **crp_audit_logs**.

## Entity–relationship (Mermaid)

```mermaid
erDiagram
    users {
        uuid id PK
        %% staff_id on service_logs references users.id
    }

    clients {
        uuid id PK
        uuid crp_id
        string first_name
        string last_name
        text ssn "encrypted"
        text dob "encrypted"
        string state_code
        string status
        timestamps
        datetime deleted_at
    }

    client_metadata {
        bigint id PK
        uuid client_id FK
        uuid crp_id
        string key
        text value "encrypted if PHI"
    }

    goals {
        bigint id PK
        uuid client_id FK
        uuid crp_id
        text description
        date start_date
        date end_date
        string status "active|completed"
        datetime created_at
    }

    service_logs {
        uuid id PK
        uuid client_id FK
        uuid staff_id FK
        bigint goal_id FK "nullable"
        text notes_master "encrypted JSON"
        string narrative_hash
        string billing_status
        string invoice_number
        datetime locked_at
        timestamps
        datetime deleted_at
    }

    note_versions {
        bigint id PK
        uuid service_log_id FK
        int version_number
        text data "encrypted JSON"
        datetime created_at
    }

    signatures {
        bigint id PK
        uuid service_log_id FK
        uuid crp_id
        string type "client|provider"
        string s3_path
        datetime signed_at
        datetime created_at
    }

    form_templates {
        bigint id PK
        string state_code
        string form_code
        string version
        json schema
        json mapping
        string pdf_template_path "S3"
    }

    form_submissions {
        uuid id PK
        uuid client_id FK
        bigint template_id FK
        uuid crp_id
        text form_data "encrypted JSON"
        string pdf_s3_key
        datetime submitted_at
    }

    crp_audit_logs {
        bigint id PK
        string request_id
        uuid crp_id
        uuid actor_id "app-level integrity"
        string action_type
        string resource_type
        string resource_id
        text old_values "encrypted"
        text new_values "encrypted"
        string ip_address
        string user_agent
        string outcome
        string action_context
        string hash "SHA-256"
        datetime created_at
    }

    clients ||--o{ client_metadata : "1:N CASCADE"
    clients ||--o{ service_logs : "1:N RESTRICT"
    clients ||--o{ goals : "1:N RESTRICT"
    clients ||--o{ form_submissions : "1:N RESTRICT"

    users ||--o{ service_logs : "staff RESTRICT"

    goals ||--o{ service_logs : "N:1 SET NULL"

    service_logs ||--o{ note_versions : "1:N CASCADE"
    service_logs ||--o{ signatures : "1:N CASCADE"

    form_templates ||--o{ form_submissions : "1:N RESTRICT"
```

## Relationships and cascade rules

| Table            | Column        | References           | Cascade rule | Notes                                        |
|------------------|---------------|----------------------|--------------|----------------------------------------------|
| client_metadata  | client_id     | clients.id           | CASCADE      | Delete client → delete metadata              |
| service_logs     | client_id     | clients.id           | RESTRICT     | Cannot delete client if logs exist           |
| service_logs     | staff_id      | users.id             | RESTRICT     | Prevent deletion of staff with logs          |
| service_logs     | goal_id       | goals.id             | SET NULL     | Goal deleted → log remains                   |
| note_versions    | service_log_id| service_logs.id      | CASCADE      | Log deleted → versions deleted               |
| signatures       | service_log_id| service_logs.id      | CASCADE      | Log deleted → signatures deleted             |
| goals            | client_id     | clients.id           | RESTRICT     | Prevent client deletion if goals exist       |
| form_submissions | client_id     | clients.id           | RESTRICT     | Client cannot be deleted if submissions exist|
| form_submissions | template_id   | form_templates.id    | RESTRICT     | Template cannot be deleted if submissions exist |

## Compliance and audit mapping

| Area | Role |
|------|------|
| **service_logs** | Central compliance entity: duplicate detection, 10-day lock, time conflicts. |
| **note_versions** | Immutable audit history for notes. |
| **signatures** | PHI / legal proof, tied to logs. |
| **form_submissions** | Validated against templates; encrypted payload; S3 references. |
| **crp_audit_logs** | Immutable, async-friendly; critical actions; optional prune/archive. |

---

*This document is a reference schema. The current mini-challenge codebase implements a reduced subset (e.g. `clients`, `service_logs`, tenant `crp_id`, encrypted PHI fields, and application-level audit storage).*
