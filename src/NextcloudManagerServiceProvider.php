<?php

namespace Sinapsteknologi\NextcloudManager;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Sinapsteknologi\NextcloudManager\Commands\CleanNextcloudFiles;

class NextcloudManagerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nextcloud.php', 'nextcloud');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanNextcloudFiles::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/nextcloud.php' => config_path('nextcloud.php'),
                __DIR__.'/../database/migrations/2025_01_01_000000_create_nextcloud_files_table.php' => database_path('migrations/' . date('Y_m_d_His') . '_create_nextcloud_files_table.php'),
            ], 'nextcloud-config');
        }

        Config::set('filesystems.disks.nextcloud', [
            'driver'   => 'nextcloud',
            'baseUri'  => env('NEXTCLOUD_URL', config('nextcloud.url')),
            'userName' => env('NEXTCLOUD_USERNAME', config('nextcloud.username')),
            'password' => env('NEXTCLOUD_PASSWORD', config('nextcloud.password')),
        ]);
    }
}
