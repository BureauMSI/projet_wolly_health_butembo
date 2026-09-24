<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Remote sync endpoint
    |--------------------------------------------------------------------------
    |
    | Empty = office-only mode: local MySQL is the source of truth and remote
    | push/pull is a no-op (no error). When set, pending ledger rows stay
    | pending until a successful push confirms them.
    |
    */
    'remote_url' => env('SYNC_REMOTE_URL', ''),

    'origin_device_id' => env('ORIGIN_DEVICE_ID', 'office-local'),

    /*
    | Shared secret for /api/sync/* between office nodes and the remote host.
    | Leave empty only in local-only deployments (API then rejects push/pull).
    */
    'token' => env('SYNC_TOKEN', ''),

    'batch_size' => (int) env('SYNC_BATCH_SIZE', 100),

    'timeout' => (int) env('SYNC_TIMEOUT', 30),
];
