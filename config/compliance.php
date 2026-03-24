<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Service log lock
    |--------------------------------------------------------------------------
    |
    | After this many days from creation, a service log is treated as locked
    | (read-only). The apply-locks command also stamps locked_at for auditing.
    |
    */
    'service_log_lock_days' => (int) env('SERVICE_LOG_LOCK_DAYS', 10),

    /*
    |--------------------------------------------------------------------------
    | Duplicate detection
    |--------------------------------------------------------------------------
    |
    | When narrative_hash matches another log for the same client within this
    | many hours, it is flagged as a potential duplicate.
    |
    */
    'duplicate_lookback_hours' => (int) env('SERVICE_LOG_DUPLICATE_LOOKBACK_HOURS', 72),

    /*
    |--------------------------------------------------------------------------
    | Time conflict enforcement
    |--------------------------------------------------------------------------
    |
    | When true, saving a service log with started_at/ended_at will throw if
    | another log for the same staff overlaps that interval.
    |
    */
    'enforce_staff_time_conflicts' => (bool) env('SERVICE_LOG_ENFORCE_TIME_CONFLICTS', false),

    /*
    |--------------------------------------------------------------------------
    | Duplicate enforcement
    |--------------------------------------------------------------------------
    |
    | When true, saving a log will fail if another duplicate exists (same client
    | and narrative_hash within the lookback window).
    |
    */
    'enforce_duplicate_detection' => (bool) env('SERVICE_LOG_ENFORCE_DUPLICATES', false),

    /*
    |--------------------------------------------------------------------------
    | PHI document storage disk
    |--------------------------------------------------------------------------
    |
    | Laravel filesystem disk name: phi_local (dev) or s3 in production.
    |
    */
    'phi_documents_disk' => env('PHI_DOCUMENTS_DISK', 'phi_local'),

];
