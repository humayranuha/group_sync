<?php

use App\Providers\AppServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;  // Example

return [
    AppServiceProvider::class,
    SanctumServiceProvider::class,  // ← Add if needed
];
