<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | This file contains feature flags that can be used to enable or disable
    | features in the application. This allows for gradual rollout of new
    | features and easy toggling of experimental functionality.
    |
    */

    // Enable new project permission system
    'project_permissions' => env('FEATURE_PROJECT_PERMISSIONS', false),
    
    // Enable super admin bypass for permissions
    'superadmin_bypass' => env('FEATURE_SUPERADMIN_BYPASS', true),
    
    // Enable project permission UI
    'project_permissions_ui' => env('FEATURE_PROJECT_PERMISSIONS_UI', false),
    
    // Enable bulk permission operations
    'bulk_permissions' => env('FEATURE_BULK_PERMISSIONS', false),
    
    // Enable permission templates
    'permission_templates' => env('FEATURE_PERMISSION_TEMPLATES', false),
    
    // Enable permission audit log
    'permission_audit' => env('FEATURE_PERMISSION_AUDIT', false),
    
    // Enable temporary permissions
    'temporary_permissions' => env('FEATURE_TEMPORARY_PERMISSIONS', false),
];