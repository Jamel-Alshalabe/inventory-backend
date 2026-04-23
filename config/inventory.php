<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Record Limit
    |--------------------------------------------------------------------------
    |
    | Hard cap on the combined number of products + movements + invoices
    | the system will accept before refusing new write operations. This
    | mirrors the Node implementation and keeps trial deployments bounded.
    */
    'record_limit' => (int) env('INVENTORY_RECORD_LIMIT', 999),

    /*
    | Threshold below which a product's quantity is considered "low".
    */
    'low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK', 5),
];
