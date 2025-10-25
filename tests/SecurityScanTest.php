<?php

use Fragly\SecurityTools\SecurityToolsServiceProvider;

class SecurityScanTest extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [SecurityToolsServiceProvider::class];
    }

    public function test_command_runs() {
        $this->artisan('security:scan')->assertExitCode(0);
    }
}