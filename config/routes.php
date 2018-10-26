<?php
declare(strict_types=1);

use BrowscapSite\Handler\DownloadHandler;
use BrowscapSite\Handler\StatsHandler;
use BrowscapSite\Handler\StreamHandler;
use BrowscapSite\Handler\UserAgentLookupHandler;
use Slim\App;

return function (App $app): void {
    $app->get('/', DownloadHandler::class);
    $app->any('/ua-lookup', UserAgentLookupHandler::class);
    $app->any('/stream', StreamHandler::class);
    $app->any('/statistics', StatsHandler::class);
};
