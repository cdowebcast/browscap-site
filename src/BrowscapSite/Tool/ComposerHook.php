<?php

namespace BrowscapSite\Tool;

use Composer\Script\Event;
use Composer\Package\PackageInterface;
use Browscap\Generator\BuildGenerator;
use Browscap\Generator\CollectionParser;
use Browscap\Helper\CollectionCreator;
use Browscap\Helper\Generator;
use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ComposerHook
{
    public static function postInstall(Event $event)
    {
        self::postUpdate($event);
    }

    public static function postUpdate(Event $event)
    {
        $composer = $event->getComposer();

        $installed = $event->getComposer()->getRepositoryManager()->getLocalRepository();

        $requiredPackage = 'browscap/browscap';

        $packages = $installed->findPackages($requiredPackage);

        if (!count($packages)) {
            throw new \Exception("The package {$requiredPackage} does not seem to be installed, and it is required.");
        }

        $package = reset($packages);
        $buildNumber = self::determineBuildNumberFromPackage($package);

        if (empty($buildNumber)) {
            throw new \Exception("Could not determine build number from package {$requiredPackage}");
        }

        $currentBuildNumber = self::getCurrentBuildNumber();
        if ($buildNumber != $currentBuildNumber)
        {
            $event->getIO()->write(sprintf('<info>Generating new Browscap build: %s</info>', $buildNumber));
            self::createBuild($buildNumber);
        } else {
            $event->getIO()->write(sprintf('<info>Current build %s is up to date</info>', $currentBuildNumber));
        }

    }

    /**
     * Try to determine the build number from a composer package
     *
     * @param \Composer\Package\PackageInterface $package
     * @return string
     */
    public static function determineBuildNumberFromPackage(PackageInterface $package)
    {
        if ($package->isDev()) {

            $buildNumber = self::determineBuildNumberFromBrowscapBuildFile();

            if (is_null($buildNumber)) {
               $buildNumber = substr($package->getSourceReference(), 0, 8);
            }
        } else {
            $installedVersion = $package->getPrettyVersion();

            // SemVer supports build numbers, but fall back to just using
            // version number if not available; at time of writing, composer
            // did not support SemVer 2.0.0 build numbers fully:
            // @see https://github.com/composer/composer/issues/2422
            $plusPos = strpos($installedVersion, '+');
            if ($plusPos !== false) {
                $buildNumber = substr($installedVersion, ($plusPos + 1));
            } else {
                $buildNumber = self::determineBuildNumberFromBrowscapBuildFile();

                if (is_null($buildNumber)) {
                   $buildNumber = $installedVersion;
                }
            }
        }

        return $buildNumber;
    }

    /**
     * This is a temporary fallback until Composer supports SemVer 2.0.0 properly
     *
     * @return string|NULL
     */
    public static function determineBuildNumberFromBrowscapBuildFile()
    {
        $buildFile = __DIR__ . '/../../../vendor/browscap/browscap/BUILD_NUMBER';

        if (file_exists($buildFile)) {
            $buildNumber = file_get_contents($buildFile);
            return trim($buildNumber);
        } else {
            return null;
        }
    }

    public static function getCurrentBuildNumber()
    {
        $buildFolder = __DIR__ . '/../../../build/';
        $metadataFile = $buildFolder . 'metadata.php';

        if (file_exists($metadataFile)) {
            $metadata = require $metadataFile;
            return $metadata['version'];
        } else {
            return null;
        }
    }

    /**
     * Generate a build for build number specified
     *
     * @param string $buildNumber
     */
    public static function createBuild($buildNumber)
    {
        $buildFolder = __DIR__ . '/../../../build/';
        $resourceFolder = __DIR__ . '/../../../vendor/browscap/browscap/resources/';

        // Create a logger
        $stream = new StreamHandler('php://output', Logger::INFO);
        $stream->setFormatter(new LineFormatter('%message%' . "\n"));

        $logger = new Logger('browscap');
        $logger->pushHandler($stream);
        $logger->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Logger::NOTICE));

        $collectionCreator = new CollectionCreator();
        $collectionParser = new CollectionParser();
        $generatorHelper = new Generator();

        // Generate the actual browscap.ini files
        $buildGenerator = new BuildGenerator($resourceFolder, $buildFolder);
        $buildGenerator
            ->setLogger($logger)
            ->setCollectionCreator($collectionCreator)
            ->setCollectionParser($collectionParser)
            ->setGeneratorHelper($generatorHelper)
            ->generateBuilds($buildNumber)
        ;

        // Generate the metadata for the site
        $rebuilder = new Rebuilder($buildFolder);
        $rebuilder->rebuild();
    }
}
