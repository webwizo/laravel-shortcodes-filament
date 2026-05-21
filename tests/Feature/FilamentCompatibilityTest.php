<?php

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Resources\Resource;
use Webwizo\ShortcodesFilament\Resources\ShortcodeResource;
use Webwizo\ShortcodesFilament\ShortcodesFilamentPlugin;

// These tests exercise the Filament API surface directly.
// If any Filament class is renamed, moved, or its interface changed between
// v3 / v4 / v5 the relevant test will fail and tell you exactly what broke.

test('Filament\Contracts\Plugin interface exists and is an interface', function () {
    expect(interface_exists(Plugin::class))->toBeTrue();
});

test('Filament\Panel class exists', function () {
    expect(class_exists(Panel::class))->toBeTrue();
});

test('Filament\Resources\Resource class exists', function () {
    expect(class_exists(Resource::class))->toBeTrue();
});

test('ShortcodesFilamentPlugin implements Filament Plugin contract', function () {
    expect(ShortcodesFilamentPlugin::class)->toImplement(Plugin::class);
});

test('ShortcodeResource extends Filament Resource', function () {
    expect(is_subclass_of(ShortcodeResource::class, Resource::class))->toBeTrue();
});

test('plugin can register its resource with a Filament panel', function () {
    $panel  = Panel::make()->id('test');
    $plugin = new ShortcodesFilamentPlugin;

    $plugin->register($panel);

    expect($panel->getResources())->toContain(ShortcodeResource::class);
});

test('resource exposes all required Filament page routes', function () {
    $pages = ShortcodeResource::getPages();

    expect($pages)->toHaveKeys(['index', 'create', 'edit']);
});

test('resource model resolves to configured Shortcode class', function () {
    expect(ShortcodeResource::getModel())
        ->toBe(\Webwizo\ShortcodesFilament\Models\Shortcode::class);
});

test('resource tenant ownership relationship name can be overridden', function () {
    ShortcodeResource::setTenantOwnershipRelationshipName('organisation');

    expect(ShortcodeResource::getTenantOwnershipRelationshipName())
        ->toBe('organisation');

    // reset
    ShortcodeResource::setTenantOwnershipRelationshipName('vendor');
});

test('Filament\Support\Enums\FontFamily enum exists with Mono case', function () {
    expect(enum_exists(\Filament\Support\Enums\FontFamily::class))->toBeTrue();
    expect(\Filament\Support\Enums\FontFamily::Mono)->toBeInstanceOf(\Filament\Support\Enums\FontFamily::class);
});

// Audit every class referenced inside ShortcodeResource. If Filament moves or removes
// any of these in a future version this test will fail and tell you exactly what broke.
test('all classes referenced in ShortcodeResource resolve at runtime', function () {
    $missing = [];

    $required = [
        // Form
        \Filament\Forms\Form::class,
        \Filament\Forms\Components\Section::class,
        \Filament\Forms\Components\TextInput::class,
        \Filament\Forms\Components\Textarea::class,
        \Filament\Forms\Components\Radio::class,
        \Filament\Forms\Components\Toggle::class,
        \Filament\Forms\Components\Select::class,
        \Filament\Forms\Components\Repeater::class,
        \Filament\Forms\Components\Placeholder::class,
        \Filament\Forms\Get::class,
        \Filament\Forms\Set::class,
        // Table structure
        \Filament\Tables\Table::class,
        \Filament\Tables\Columns\TextColumn::class,
        \Filament\Tables\Columns\ToggleColumn::class,
        \Filament\Tables\Filters\TernaryFilter::class,
        \Filament\Tables\Filters\SelectFilter::class,
        // Table actions
        \Filament\Tables\Actions\EditAction::class,
        \Filament\Tables\Actions\DeleteAction::class,
        \Filament\Tables\Actions\BulkActionGroup::class,
        \Filament\Tables\Actions\DeleteBulkAction::class,
        // Support
        \Filament\Support\Enums\FontFamily::class,
    ];

    foreach ($required as $class) {
        if (! class_exists($class) && ! interface_exists($class) && ! enum_exists($class)) {
            $missing[] = $class;
        }
    }

    expect($missing)->toBeEmpty('These classes are missing: ' . implode(', ', $missing));
});
