<?php

namespace BrowscapSite\Controller;

use BrowscapSite\BrowscapSiteWeb;

class DownloadController
{
    protected $app;
    protected $fileList;

    public function __construct(BrowscapSiteWeb $app, array $fileList)
    {
        $this->app = $app;
        $this->fileList = $fileList;
    }

    public function indexAction()
    {
        $metadata = $this->getMetadata();

        $this->mergeMetadataToFiles($metadata, $this->fileList);

        $baseHost = 'http://' . $_SERVER['SERVER_NAME'];

        $releaseDate = new \DateTime($metadata['released']);

        return $this->app['twig']->render('downloads.html', array(
            'files' => $this->fileList,
            'version' => $metadata['version'],
            'releaseDate' => $releaseDate->format('jS M Y'),
            'baseHost' => $baseHost,
        ));
    }

    public function mergeMetadataToFiles($metadata, &$files)
    {
        $files['asp']['BrowsCapINI']['size'] = $metadata['filesizes']['BrowsCapINI'];
        $files['asp']['Full_BrowsCapINI']['size'] = $metadata['filesizes']['Full_BrowsCapINI'];
        $files['asp']['Lite_BrowsCapINI']['size'] = $metadata['filesizes']['Lite_BrowsCapINI'];
        $files['php']['PHP_BrowsCapINI']['size'] = $metadata['filesizes']['PHP_BrowsCapINI'];
        $files['php']['Full_PHP_BrowsCapINI']['size'] = $metadata['filesizes']['Full_PHP_BrowsCapINI'];
        $files['php']['Lite_PHP_BrowsCapINI']['size'] = $metadata['filesizes']['Lite_PHP_BrowsCapINI'];
    }

    public function getMetadata()
    {
        return require_once(__DIR__ . '/../../../build/metadata.php');
    }
}