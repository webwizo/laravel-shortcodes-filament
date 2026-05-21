<?php

use Filament\Contracts\Plugin;
use Webwizo\ShortcodesFilament\ShortcodesFilamentPlugin;

test('plugin implements Filament Plugin contract', function () {
    expect(ShortcodesFilamentPlugin::class)->toImplement(Plugin::class);
});

test('plugin id is laravel-shortcodes', function () {
    expect((new ShortcodesFilamentPlugin)->getId())->toBe('laravel-shortcodes');
});

test('plugin navigation defaults are correct', function () {
    $plugin = new ShortcodesFilamentPlugin;

    expect($plugin->getNavigationGroup())->toBe('Content')
        ->and($plugin->getNavigationIcon())->toBe('heroicon-o-code-bracket')
        ->and($plugin->getNavigationSort())->toBeNull()
        ->and($plugin->isScopedToTenant())->toBeFalse()
        ->and($plugin->hasDynamicDataSources())->toBeTrue();
});

test('plugin fluent navigation setters work', function () {
    $plugin = (new ShortcodesFilamentPlugin)
        ->navigationGroup('Settings')
        ->navigationIcon('heroicon-o-tag')
        ->navigationSort(5)
        ->scopedToTenant()
        ->usingDynamicDataSources(false);

    expect($plugin->getNavigationGroup())->toBe('Settings')
        ->and($plugin->getNavigationIcon())->toBe('heroicon-o-tag')
        ->and($plugin->getNavigationSort())->toBe(5)
        ->and($plugin->isScopedToTenant())->toBeTrue()
        ->and($plugin->hasDynamicDataSources())->toBeFalse();
});

test('plugin tenant fluent setters work', function () {
    $plugin = (new ShortcodesFilamentPlugin)
        ->tenantForeignKey('org_id')
        ->tenantRelationship('organisation')
        ->tenantModel('App\Models\Organisation');

    expect($plugin->getTenantForeignKey())->toBe('org_id')
        ->and($plugin->getTenantRelationship())->toBe('organisation')
        ->and($plugin->getTenantModel())->toBe('App\Models\Organisation');
});

test('getTenantKeyType explicit override takes priority', function () {
    $plugin = (new ShortcodesFilamentPlugin)->tenantKeyType('uuid');
    expect($plugin->getTenantKeyType())->toBe('uuid');
});

test('getTenantKeyType falls back to int with no model or config', function () {
    config(['shortcodes-filament.tenant.key_type' => null]);
    config(['shortcodes-filament.tenant.model' => null]);

    expect((new ShortcodesFilamentPlugin)->getTenantKeyType())->toBe('int');
});

test('getTenantKeyType reads from config when no explicit override', function () {
    config(['shortcodes-filament.tenant.key_type' => 'ulid']);
    config(['shortcodes-filament.tenant.model' => null]);

    expect((new ShortcodesFilamentPlugin)->getTenantKeyType())->toBe('ulid');
});
