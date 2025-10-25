<?php

namespace Fragly\SecurityTools;

use Fragly\SecurityTools\Commands\SecurityScanCommand;
use Illuminate\Support\ServiceProvider;

class SecurityToolsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/security-tools.php', 'security-tools');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/security-tools.php' => config_path('security-tools.php'),
            ], 'config');

            $this->commands([
                SecurityScanCommand::class
            ]);
        }
    }
}