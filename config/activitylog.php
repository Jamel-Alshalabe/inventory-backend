<?php

return [
    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => 365,

    /*
     * If no log name is passed to the activity() helper
     * we use this default log name.
     */
    'default_log_name' => 'default',

    /*
     * You can specify an auth driver here that will be used to retrieve the
     * currently logged in user. When this is null we'll use the default
     * Laravel auth driver.
     */
    'default_auth_driver' => null,

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
     * When running in console, we'll automatically use the default auth driver.
     * You can override this with the following option.
     */
    'default_console_auth_driver' => null,

    /*
     * The model that will be used for the activity log. Make sure the
     * model extends the Spatie\Activitylog\Models\Activity model.
     */
    'activity_model' => \App\Models\Activity::class,

    /*
     * The model that will be used for the activity log. Make sure the
     * model extends the Spatie\Activitylog\Models\Activity model.
     */
    'subject_model' => \Spatie\Activitylog\Contracts\Activity::class,

    /*
     * Table name that will be used for the activity log.
     */
    'table_name' => 'activity_log',

    /*
     * This is the name of the connection that will be used to write to the
     * activity log table. When this value is null the default database
     * connection will be used.
     */
    'database_connection' => null,

    /*
     * This is the name of the table that will be used by the batch migration.
     * Make sure the table name doesn't conflict with any existing table.
     */
    'batch_table_name' => 'activity_log_batches',

    /*
     * When enabled, only activities that have changed attributes will be stored.
     */
    'submit_empty_logs' => env('ACTIVITY_LOGGER_SUBMIT_EMPTY_LOGS', true),

    /*
     * When enabled, all log entries will be grouped by the same subject and
     * the same causer within the same request.
     */
    'log_batch_uuid' => env('ACTIVITY_LOGGER_LOG_BATCH_UUID', false),

    /*
     * When enabled, the package will log the changed attributes of a model.
     */
    'log_attributes' => env('ACTIVITY_LOGGER_LOG_ATTRIBUTES', true),

    /*
     * When enabled, the package will log the changed attributes of a model
     * when the model is updated.
     */
    'log_changed_attributes_only' => env('ACTIVITY_LOGGER_LOG_CHANGED_ATTRIBUTES_ONLY', false),

    /*
     * When enabled, the package will log the attributes of a model when the
     * model is created.
     */
    'log_attributes_when_creating' => env('ACTIVITY_LOGGER_LOG_ATTRIBUTES_WHEN_CREATING', true),

    /*
     * When enabled, the package will log the attributes of a model when the
     * model is deleted.
     */
    'log_attributes_when_deleting' => env('ACTIVITY_LOGGER_LOG_ATTRIBUTES_WHEN_DELETING', true),

    /*
     * When enabled, the package will log the attributes of a model when the
     * model is restored.
     */
    'log_attributes_when_restoring' => env('ACTIVITY_LOGGER_LOG_ATTRIBUTES_WHEN_RESTORING', true),

    /*
     * The default value for the `log_name` option of the activitylog.
     */
    'default_log_name' => env('ACTIVITY_LOGGER_DEFAULT_LOG_NAME', 'default'),

    /*
     * The default value for the `causer_type` option of the activitylog.
     */
    'default_causer_type' => env('ACTIVITY_LOGGER_DEFAULT_CAUSER_TYPE', 'App\\Models\\User'),

    /*
     * The default value for the `causer_id` option of the activitylog.
     */
    'default_causer_id' => env('ACTIVITY_LOGGER_DEFAULT_CAUSER_ID', null),

    /*
     * The default value for the `subject_type` option of the activitylog.
     */
    'default_subject_type' => env('ACTIVITY_LOGGER_DEFAULT_SUBJECT_TYPE', null),

    /*
     * The default value for the `subject_id` option of the activitylog.
     */
    'default_subject_id' => env('ACTIVITY_LOGGER_DEFAULT_SUBJECT_ID', null),

    /*
     * The default value for the `description` option of the activitylog.
     */
    'default_description' => env('ACTIVITY_LOGGER_DEFAULT_DESCRIPTION', null),

    /*
     * The default value for the `properties` option of the activitylog.
     */
    'default_properties' => env('ACTIVITY_LOGGER_DEFAULT_PROPERTIES', null),

    /*
     * The default value for the `batch_uuid` option of the activitylog.
     */
    'default_batch_uuid' => env('ACTIVITY_LOGGER_DEFAULT_BATCH_UUID', null),
];
