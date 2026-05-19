<?php

namespace Webwizo\ShortcodesFilament\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string  compile(string $value)         Compile shortcode tags in the given string and return the result.
 * @method static string  strip(string $value)           Remove all shortcode tags from the given string without processing them.
 * @method static \Webwizo\Shortcodes\Shortcode register(string $name, callable|string $callback) Register a shortcode handler.
 * @method static \Webwizo\Shortcodes\Shortcode enable()  Enable shortcode processing.
 * @method static \Webwizo\Shortcodes\Shortcode disable() Disable shortcode processing.
 *
 * @see \Webwizo\Shortcodes\Shortcode
 */
class Shortcode extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'shortcode';
    }
}
