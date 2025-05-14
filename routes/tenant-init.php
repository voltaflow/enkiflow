<?php

/*
|--------------------------------------------------------------------------
| Tenant Initialization Routes
|--------------------------------------------------------------------------
|
| This file is loaded ONLY for tenant domains, not for main domains.
| It ensures tenancy is initialized correctly without causing errors.
|
*/

use Illuminate\Support\Facades\Route;

// Special logging to debug tenant initialization
\Log::info("Initializing tenancy for host: " . request()->getHost());

// No routes needed here - this file is just to initialize tenancy
// The actual tenant routes are in tenant.php