<?php

return [
    'chunk_mode' => env('ELASTICSEARCH_INDEX_MODE', 'after_id'),
    'host' => env('ELASTICSEARCH_PORT') && env('ELASTICSEARCH_SCHEME')
        ? env('ELASTICSEARCH_SCHEME') . '://' . env('ELASTICSEARCH_HOST') . ':' . env('ELASTICSEARCH_PORT')
        : env('ELASTICSEARCH_HOST'),
    'user' => env('ELASTICSEARCH_USER'),
    'password' => env('ELASTICSEARCH_PASSWORD', env('ELASTICSEARCH_PASS')),
    'cloud_id' => env('ELASTICSEARCH_CLOUD_ID', env('ELASTICSEARCH_API_ID')),
    'api_key' => env('ELASTICSEARCH_API_KEY'),
    'ssl_verification' => env('ELASTICSEARCH_SSL_VERIFICATION', true),
    /*
     * Escape Lucene reserved characters in the string passed to Model::search()
     * before it becomes a query_string. Off, the string is the query_string
     * syntax itself (a stray `"` is a parse error). Wrap a query in a class
     * implementing ElasticSearch\RawQuery to bypass escaping when this is on.
     */
    'escape_query' => env('ELASTICSEARCH_ESCAPE_QUERY', false),
    'queue' => [
        'timeout' => env('SCOUT_QUEUE_TIMEOUT'),
    ],
    'indices' => [
        'mappings' => [
            'default' => [
                'properties' => [
                    'id' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
        ],
        'settings' => [
            'default' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ],
        ],
    ],
];
