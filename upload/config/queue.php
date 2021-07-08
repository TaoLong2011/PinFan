<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

return [
    'default'     => 'redis',
    'connections' => [
        'sync'     => [
            'type' => 'sync',
        ],
        'database' => [
            'type'  => 'database',
            'queue' => env('queue_name', 'default'),
            'table' => 'jobs',
        ],
        'redis'    => [
            'type'       => 'redis',
            'queue'      => env('queue_name', 'default'),
            'host'       => env('redis.redis_hostname','127.0.0.1'),
            'port'       => env('redis.port', '6379'),
            'password'   => env('redis.redis_password', 'redis123'),
            'select'     => env('redis.select', 0),
            'timeout'    => 0,
            'persistent' => false,
        ],
    ],
    'failed'      => [
        'type'  => 'none',
        'table' => 'failed_jobs',
    ],
];