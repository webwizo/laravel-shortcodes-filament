<?php

namespace Webwizo\LaravelShortcodesFilament\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Webwizo\ShortcodesFilament\ShortcodesFilamentServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ShortcodesFilamentServiceProvider::class,
        ];
    }
}