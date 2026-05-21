<?php

namespace Webwizo\LaravelShortcodesFilament\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Webwizo\LaravelShortcodesFilament\LaravelShortcodesFilamentServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelShortcodesFilamentServiceProvider::class,
        ];
    }
}