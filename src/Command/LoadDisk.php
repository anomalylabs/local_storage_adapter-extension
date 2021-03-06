<?php namespace Anomaly\LocalStorageAdapterExtension\Command;

use Anomaly\ConfigurationModule\Configuration\Contract\ConfigurationRepositoryInterface;
use Anomaly\FilesModule\Disk\Adapter\AdapterFilesystem;
use Anomaly\FilesModule\Disk\Contract\DiskInterface;
use Anomaly\Streams\Platform\Application\Application;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\FilesystemManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\MountManager;

/**
 * Class LoadDisk
 *
 * @link          http://pyrocms.com/
 * @author        PyroCMS, Inc. <support@pyrocms.com>
 * @author        Ryan Thompson <ryan@pyrocms.com>
 */
class LoadDisk
{

    /**
     * The disk instance.
     *
     * @var DiskInterface
     */
    protected $disk;

    /**
     * Create a new LoadDisk instance.
     *
     * @param DiskInterface $disk
     */
    public function __construct(DiskInterface $disk)
    {
        $this->disk = $disk;
    }

    /**
     * Handle the command.
     * 
     * @param Repository                       $config
     * @param MountManager                     $flysystem
     * @param Application                      $application
     * @param FilesystemManager                $filesystem
     * @param ConfigurationRepositoryInterface $configuration
     */
    public function handle(
        Repository $config,
        MountManager $flysystem,
        Application $application,
        FilesystemManager $filesystem,
        ConfigurationRepositoryInterface $configuration
    ) {

        $private = $configuration->value(
            'anomaly.extension.local_storage_adapter::private',
            $this->disk->getSlug(),
            false
        );

        if ($private) {
            $root = $application->getStoragePath("files-module/{$this->disk->getSlug()}");
        } else {
            $root = $application->getAssetsPath("files-module/{$this->disk->getSlug()}");
        }

        $driver = new AdapterFilesystem(
            $this->disk,
            new Local($root),
            [
                'base_url' => $root,
            ]
        );

        $flysystem->mountFilesystem($this->disk->getSlug(), $driver);

        $filesystem->extend(
            $this->disk->getSlug(),
            function () use ($driver) {
                return $driver;
            }
        );

        $config->set(
            'filesystems.disks.' . $this->disk->getSlug(),
            [
                'driver' => $this->disk->getSlug(),
                'root'   => $root,
            ]
        );
    }
}
