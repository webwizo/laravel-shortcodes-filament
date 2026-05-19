<?php

namespace Webwizo\ShortcodesFilament\Shortcodes;

use Webwizo\ShortcodesFilament\Models\Shortcode;

class DynamicShortcode
{
    public function __construct(
        protected Shortcode $model
    ) {}

    public function register(
        object  $shortcode,
        ?string $content,
        mixed   $compiler,
        string  $name,
        ?array  $viewData = null
    ): string {
        return $this->model->render($shortcode, $content);
    }

    /**
     * Make the class directly invokable so it works as a callable
     * whether registered as an object, [$object, 'register'], or a closure.
     */
    public function __invoke(
        object  $shortcode,
        ?string $content,
        mixed   $compiler,
        string  $name,
        ?array  $viewData = null
    ): string {
        return $this->model->render($shortcode, $content);
    }
}