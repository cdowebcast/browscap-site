<?php
declare(strict_types=1);

return [
    'db' => [],
    'debug' => false,
    \Zend\ConfigAggregator\ConfigAggregator::ENABLE_CACHE => true,
    'rateLimiter' => [
        'rateLimitDownloads' => 10,    // How many downloads per $rateLimitPeriod
        'rateLimitPeriod' => 1,        // Download limit period in HOURS
        'tempBanPeriod' => 3,        // Tempban period in DAYS
        'tempBanLimit' => 5,        // How many tempbans allowed in $tempBanPeriod before permaban
    ],
];
