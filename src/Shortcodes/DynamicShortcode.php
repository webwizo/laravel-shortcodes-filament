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
        array   $viewData
    ): string {
        return $this->model->render($shortcode, $content);
    }
}