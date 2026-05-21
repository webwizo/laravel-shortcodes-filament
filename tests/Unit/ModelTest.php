<?php

use Webwizo\ShortcodesFilament\Models\Shortcode;

// ── isStatic / isDynamic ─────────────────────────────────────────────────────

test('isStatic returns true for static type', function () {
    $model = new Shortcode(['type' => 'static']);
    expect($model->isStatic())->toBeTrue()
        ->and($model->isDynamic())->toBeFalse();
});

test('isDynamic returns true for dynamic type', function () {
    $model = new Shortcode(['type' => 'dynamic']);
    expect($model->isDynamic())->toBeTrue()
        ->and($model->isStatic())->toBeFalse();
});

test('isStatic returns true when type is null', function () {
    $model = new Shortcode(['type' => null]);
    expect($model->isStatic())->toBeTrue();
});

// ── render() ─────────────────────────────────────────────────────────────────

test('render replaces content placeholder', function () {
    $model = new Shortcode([
        'type'     => 'static',
        'template' => '<p>{{content}}</p>',
    ]);

    expect($model->render((object) [], 'Hello World'))->toBe('<p>Hello World</p>');
});

test('render handles null content gracefully', function () {
    $model = new Shortcode([
        'type'     => 'static',
        'template' => '<p>{{content}}</p>',
    ]);

    expect($model->render((object) [], null))->toBe('<p></p>');
});

test('render replaces named attribute placeholder with provided value', function () {
    $model = new Shortcode([
        'type'       => 'static',
        'template'   => '<div class="{{class}}">text</div>',
        'attributes' => [['name' => 'class', 'default' => 'btn']],
    ]);

    $tag = (object) ['class' => 'btn-primary'];
    expect($model->render($tag, null))->toBe('<div class="btn-primary">text</div>');
});

test('render falls back to attribute default when value not provided', function () {
    $model = new Shortcode([
        'type'       => 'static',
        'template'   => '<div class="{{class}}">text</div>',
        'attributes' => [['name' => 'class', 'default' => 'btn-default']],
    ]);

    expect($model->render((object) [], null))->toBe('<div class="btn-default">text</div>');
});

test('render escapes attribute values to prevent XSS', function () {
    $model = new Shortcode([
        'type'       => 'static',
        'template'   => '<div class="{{class}}">text</div>',
        'attributes' => [['name' => 'class', 'default' => '']],
    ]);

    $tag = (object) ['class' => '<script>alert(1)</script>'];
    expect($model->render($tag, null))
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>');
});

test('render builds {{attr}} from all non-empty attributes', function () {
    $model = new Shortcode([
        'type'       => 'static',
        'template'   => '<div {{attr}}>text</div>',
        'attributes' => [
            ['name' => 'class',  'default' => ''],
            ['name' => 'id',     'default' => ''],
        ],
    ]);

    $tag = (object) ['class' => 'bold', 'id' => 'main'];
    expect($model->render($tag, null))->toBe('<div class="bold" id="main">text</div>');
});

test('dynamic shortcode returns empty string when lookup attribute is missing', function () {
    $model = new Shortcode([
        'type'             => 'dynamic',
        'template'         => '<div>{{db.name}}</div>',
        'data_source_table' => 'products',
        'data_source_key'  => 'id',
        'data_source_attr' => 'id',
    ]);

    // No 'id' attribute on the tag object
    expect($model->render((object) [], null))->toBe('');
});

// ── getUsageExampleAttribute ─────────────────────────────────────────────────

test('usage example for shortcode with no attributes', function () {
    $model = new Shortcode(['tag' => 'hr', 'type' => 'static', 'attributes' => []]);
    expect($model->usage_example)->toBe('[hr]content[/hr]');
});

test('usage example includes attribute defaults', function () {
    $model = new Shortcode([
        'tag'        => 'button',
        'type'       => 'static',
        'attributes' => [['name' => 'class', 'default' => 'btn']],
    ]);

    expect($model->usage_example)->toBe('[button class="btn"]content[/button]');
});

test('usage example for dynamic shortcode prepends lookup attribute', function () {
    $model = new Shortcode([
        'tag'              => 'store',
        'type'             => 'dynamic',
        'data_source_attr' => 'id',
        'attributes'       => [['name' => 'class', 'default' => 'store-card']],
    ]);

    expect($model->usage_example)->toBe('[store id="" class="store-card"]content[/store]');
});
