<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Only Include Routes
    |--------------------------------------------------------------------------
    |
    | Only include routes that match the given patterns. If this is set,
    | all other routes will be excluded.
    |
    */
    'only' => null,

    /*
    |--------------------------------------------------------------------------
    | Except Routes
    |--------------------------------------------------------------------------
    |
    | Exclude routes that match the given patterns.
    |
    */
    'except' => [
        'horizon.*',
        'cashier.*',
        'stancl.*',
        'health.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    |
    | Array of route groups that should be included in the generated routes.
    |
    */
    'groups' => [
        // This ensures tenant routes are included when in tenant context
        'tenant.*' => function () {
            return function_exists('tenant') && tenant();
        },
    ],

    /*
    |--------------------------------------------------------------------------
    | Skip Route Function
    |--------------------------------------------------------------------------
    |
    | A callback that determines if a route should be skipped.
    |
    */
    'skip' => function ($route) {
        // Include tenant routes only when in tenant context
        if (str_starts_with($route->getName() ?? '', 'tenant.')) {
            return !function_exists('tenant') || !tenant();
        }
        return false;
    },
];