<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL', 'http://localhost:8000') . '/auth/google/callback',
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('APP_URL', 'http://localhost:8000') . '/auth/facebook/callback',
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'wompi' => [
        'env'              => env('WOMPI_ENV', 'sandbox'), // sandbox | production
        'public_key'       => env('WOMPI_PUBLIC_KEY'),
        'private_key'      => env('WOMPI_PRIVATE_KEY'),
        'events_secret'    => env('WOMPI_EVENTS_SECRET'),
        'integrity_secret' => env('WOMPI_INTEGRITY_SECRET'),
        'checkout_url'     => env('WOMPI_CHECKOUT_URL', 'https://checkout.wompi.co/p/'),
        'api_url'          => env('WOMPI_API_URL', env('WOMPI_ENV', 'sandbox') === 'production'
            ? 'https://production.wompi.co/v1'
            : 'https://sandbox.wompi.co/v1'),
        // Tolerancia (segundos) del timestamp del evento. Wompi reintenta hasta 24h.
        'timestamp_tolerance' => (int) env('WOMPI_TIMESTAMP_TOLERANCE', 86400),
    ],

    'envia' => [
        'env'      => env('ENVIA_ENV', 'sandbox'),
        'token'    => env('ENVIA_API_TOKEN'),
        'api_url'  => env('ENVIA_API_URL', env('ENVIA_ENV', 'sandbox') === 'production'
            ? 'https://api.envia.com'
            : 'https://api-test.envia.com'),
        'queries_url' => env('ENVIA_QUERIES_URL', env('ENVIA_ENV', 'sandbox') === 'production'
            ? 'https://queries.envia.com'
            : 'https://queries-test.envia.com'),
        'default_carrier' => env('ENVIA_DEFAULT_CARRIER', 'servientrega'),
        'webhook_secret'  => env('ENVIA_WEBHOOK_SECRET'),
        'origin' => [
            'name'        => env('ENVIA_ORIGIN_NAME', 'FusaShop'),
            'company'     => env('ENVIA_ORIGIN_COMPANY', 'FusaShop'),
            'email'       => env('ENVIA_ORIGIN_EMAIL', 'despachos@fusashop.com'),
            'phone'       => env('ENVIA_ORIGIN_PHONE', '3000000000'),
            'street'      => env('ENVIA_ORIGIN_STREET', 'Calle 1'),
            'number'      => env('ENVIA_ORIGIN_NUMBER', '1-1'),
            'district'    => env('ENVIA_ORIGIN_DISTRICT', 'Centro'),
            'city'        => env('ENVIA_ORIGIN_CITY', 'Fusagasuga'),
            'state'       => env('ENVIA_ORIGIN_STATE', 'CU'),
            'country'     => 'CO',
            'postalCode'  => env('ENVIA_ORIGIN_POSTAL_CODE', '252211'),
        ],
        // Valores por defecto cuando el producto no define peso/dimensiones
        'default_weight_kg' => (float) env('ENVIA_DEFAULT_WEIGHT_KG', 0.5),
        'default_dims_cm'   => [10, 10, 10],
    ],

    'factus' => [
        'env'           => env('FACTUS_ENV', 'sandbox'),
        'api_url'       => env('FACTUS_API_URL', env('FACTUS_ENV', 'sandbox') === 'production'
            ? 'https://api.factus.com.co'
            : 'https://api-sandbox.factus.com.co'),
        'client_id'     => env('FACTUS_CLIENT_ID'),
        'client_secret' => env('FACTUS_CLIENT_SECRET'),
        'username'      => env('FACTUS_USERNAME'),
        'password'      => env('FACTUS_PASSWORD'),
        'numbering_range_id' => env('FACTUS_NUMBERING_RANGE_ID'),
        'default_municipality_id' => env('FACTUS_DEFAULT_MUNICIPALITY_ID', 149),
        'unit_measure_id' => env('FACTUS_UNIT_MEASURE_ID', 70),
        'iva_rate'      => (float) env('FACTUS_IVA_RATE', 19),
        'prices_include_tax' => (bool) env('FACTUS_PRICES_INCLUDE_TAX', true),
    ],

    'fusashop' => [
        'commission_rate' => (float) env('PLATFORM_COMMISSION_RATE', 0.08),
        'reservation_minutes' => (int) env('STOCK_RESERVATION_MINUTES', 30),
        'mcp_token' => env('MCP_TOKEN'),
    ],

];