<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laratrust User Model
    |--------------------------------------------------------------------------
    |
    | This is the model that will be used as the user model. In most cases
    | you will want to use the App\Models\User model, but you are free to
    | use any model you want.
    |
    */
    'user' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Laratrust Models
    |--------------------------------------------------------------------------
    |
    | These are the models that will be used by Laratrust to create the
    | relations between the user and the roles and permissions.
    |
    */
    'models' => [
        'role' => 'App\\Models\\Role',
        'permission' => 'App\\Models\\Permission',
    ],

    /*
    |--------------------------------------------------------------------------
    | Laratrust Tables
    |--------------------------------------------------------------------------
    |
    | These are the tables that will be created by Laratrust to store the
    | roles, permissions and the relations between the user and the roles
    | and permissions.
    |
    */
    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'role_user' => 'role_user',
        'permission_user' => 'permission_user',
        'permission_role' => 'permission_role',
    ],

    /*
    |--------------------------------------------------------------------------
    | Laratrust Foreign Keys
    |--------------------------------------------------------------------------
    |
    | These are the foreign keys that will be used by Laratrust to create the
    | relations between the user and the roles and permissions.
    |
    */
    'foreign_keys' => [
        'user' => 'user_id',
        'role' => 'role_id',
        'permission' => 'permission_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Laratrust Middleware
    |--------------------------------------------------------------------------
    |
    | This configuration helps to customize the Laratrust middleware behavior.
    |
    */
    'middleware' => [
        'handle' => 'Laratrust\Middleware\LaratrustMiddleware',
        'role' => 'Laratrust\Middleware\LaratrustRole',
        'permission' => 'Laratrust\Middleware\LaratrustPermission',
        'ability' => 'Laratrust\Middleware\LaratrustAbility',
    ],

    /*
    |--------------------------------------------------------------------------
    | Laratrust Magic 'can' Method
    |--------------------------------------------------------------------------
    |
    | This configuration helps to customize the Laratrust magic 'can' method
    | behavior. When set to true, it will check if the user has the required
    | permission, otherwise it will return false.
    |
    */
    'magic_can_method' => true,

    /*
    |--------------------------------------------------------------------------
    | Laratrust Cache
    |--------------------------------------------------------------------------
    |
    | Here you may specify if you want to cache the roles and permissions
    | for the users. This is useful if you have a lot of roles and permissions
    | and you want to improve the performance of your application.
    |
    */
    'cache' => [
        'enabled' => false,
        'key' => 'laratrust.cache',
        'ttl' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Laratrust Teams
    |--------------------------------------------------------------------------
    |
    | Here you may specify if you want to use teams in your application.
    | If you set this to true, the teams functionality will be enabled.
    |
    */
    'teams' => false,

    /*
    |--------------------------------------------------------------------------
    | Laratrust Strict Check
    |--------------------------------------------------------------------------
    |
    | This configuration helps to customize the Laratrust strict check behavior.
    | When set to true, it will check if the user has the required role or
    | permission, otherwise it will return false.
    |
    */
    'strict' => false,

    /*
    |--------------------------------------------------------------------------
    | Laratrust Register Permissions
    |--------------------------------------------------------------------------
    |
    | Here you may specify if you want to register the permissions in the
    | service container. This is useful if you want to use the permissions
    | in your application.
    |
    */
    'register_permissions' => true,

    /*
    |--------------------------------------------------------------------------
    | Laratrust Permissions as Guards
    |--------------------------------------------------------------------------
    |
    | Here you may specify if you want to use the permissions as guards in
    | your application. This is useful if you want to use the permissions
    | in your routes.
    |
    */
    'permissions_as_guards' => false,
];
