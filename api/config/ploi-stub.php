<?php

return [
    /*
     * Bearer token the panel authenticates with. When null, any non-empty
     * bearer token is accepted (convenient for local development).
     */
    'token' => env('PLOI_STUB_TOKEN'),

    /*
     * Seconds between resource creation and the stub reporting status "active".
     * Mirrors the polling behaviour of ploi-core's Fetch*Status jobs.
     */
    'provision_delay' => (int) env('PLOI_STUB_PROVISION_DELAY', 2),
];
