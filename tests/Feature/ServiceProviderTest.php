<?php

use Illuminate\Support\Facades\Schema;
use Webwizo\ShortcodesFilament\Models\Shortcode;

test('config is merged by service provider', function () {
    expect(config('shortcodes-filament.model'))->toBe(Shortcode::class)
        ->and(config('shortcodes-filament.table_name'))->toBe('shortcodes')
        ->and(config('shortcodes-filament.cache_ttl'))->toBe(600);
});

test('shortcodes table is created by package migrations', function () {
    expect(Schema::hasTable('shortcodes'))->toBeTrue();
});

test('shortcodes table has all required columns', function () {
    $columns = Schema::getColumnListing('shortcodes');

    expect($columns)
        ->toContain('id')
        ->toContain('tag')
        ->toContain('label')
        ->toContain('description')
        ->toContain('template')
        ->toContain('attributes')
        ->toContain('type')
        ->toContain('is_active')
        ->toContain('data_source_table')
        ->toContain('data_source_key')
        ->toContain('data_source_attr')
        ->toContain('created_at')
        ->toContain('updated_at');
});

test('shortcode model can be persisted and retrieved', function () {
    Shortcode::create([
        'tag'      => 'hello-world',
        'label'    => 'Hello World',
        'type'     => 'static',
        'template' => '<p>{{content}}</p>',
        'is_active' => true,
    ]);

    $found = Shortcode::where('tag', 'hello-world')->first();

    expect($found)->not->toBeNull()
        ->and($found->label)->toBe('Hello World')
        ->and($found->is_active)->toBeTrue()
        ->and($found->isStatic())->toBeTrue();
});

test('shortcode attributes json column round-trips correctly', function () {
    Shortcode::create([
        'tag'        => 'btn',
        'label'      => 'Button',
        'type'       => 'static',
        'template'   => '<button class="{{class}}">{{content}}</button>',
        'attributes' => [['name' => 'class', 'default' => 'btn-primary']],
        'is_active'  => true,
    ]);

    $found = Shortcode::where('tag', 'btn')->first();

    expect($found->getAttributeValue('attributes'))
        ->toBeArray()
        ->toHaveCount(1)
        ->and($found->getAttributeValue('attributes')[0]['name'])->toBe('class')
        ->and($found->getAttributeValue('attributes')[0]['default'])->toBe('btn-primary');
});

test('shortcode tag must be unique', function () {
    Shortcode::create([
        'tag' => 'duplicate', 'label' => 'First', 'type' => 'static', 'template' => 'a',
    ]);

    expect(fn () => Shortcode::create([
        'tag' => 'duplicate', 'label' => 'Second', 'type' => 'static', 'template' => 'b',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
