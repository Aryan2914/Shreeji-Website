<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    */

    'driver' => env('SCOUT_DRIVER', 'meilisearch'),

    'prefix' => env('SCOUT_PREFIX', ''),

    'queue' => env('SCOUT_QUEUE', false),

    'after_commit' => false,

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY', 'masterKey123'),
        'index-settings' => [
            \App\Models\Product::class => [
                'filterableAttributes' => [
                    'category_id',
                    'category_slug',
                    'brand_id',
                    'brand_slug',
                    'is_featured',
                    'is_express_eligible',
                    'in_stock',
                    'min_price',
                    'max_price',
                ],
                'sortableAttributes' => [
                    'min_price',
                    'created_at_ts',
                    'name',
                ],
                'searchableAttributes' => [
                    'name',
                    'search_text',
                    'sku_codes',
                    'brand',
                    'category',
                    'short_description',
                ],
                'synonyms' => [
                    '4-pin' => ['4 pin', '4pin', '4P', '4-P', '4 Pole', '4pole'],
                    '4 pin' => ['4-pin', '4pin', '4P', '4-P'],
                    '4P' => ['4-pin', '4 pin', '4pin', '4 Pole'],
                    '5m' => ['5 m', '5meter', '5 meters', '5 metre'],
                    '5 m' => ['5m', '5meter', '5 meters', '5 metre'],
                    'ddr4' => ['ddr-4', 'DDR4', 'DDR-4', 'pc4'],
                    'ddr-4' => ['ddr4', 'DDR4', 'DDR-4'],
                    'DDR4' => ['ddr4', 'ddr-4', 'DDR-4'],
                    'nvme' => ['nvme ssd', 'm.2 nvme', 'pcie nvme', 'm2 nvme'],
                    '1tb' => ['1 tb', '1000gb', '1000 gb', '1024gb'],
                    '1 tb' => ['1tb', '1000gb', '1000 gb'],
                    'cat6' => ['cat-6', 'CAT6', 'CAT-6', 'category 6'],
                    'patch cord' => ['patch cable', 'lan cable', 'ethernet cable', 'rj45 cable'],
                    'patch cable' => ['patch cord', 'lan cable', 'ethernet cable', 'rj45 cable'],
                ],
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                ],
            ],
        ],
    ],

];
