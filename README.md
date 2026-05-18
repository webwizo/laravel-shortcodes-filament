# Laravel Shortcodes — Filament Plugin

[![Latest Version on Packagist](https://img.shields.io/packagist/v/webwizo/laravel-shortcodes-filament.svg?style=flat-square)](https://packagist.org/packages/webwizo/laravel-shortcodes-filament)
[![Total Downloads](https://img.shields.io/packagist/dt/webwizo/laravel-shortcodes-filament.svg?style=flat-square)](https://packagist.org/packages/webwizo/laravel-shortcodes-filament)
[![License](https://img.shields.io/packagist/l/webwizo/laravel-shortcodes-filament.svg?style=flat-square)](https://packagist.org/packages/webwizo/laravel-shortcodes-filament)

A [Filament v3](https://filamentphp.com) plugin that gives you a full admin UI for managing [webwizo/laravel-shortcodes](https://github.com/webwizo/laravel-shortcodes) — create, edit, and delete shortcodes from the browser without touching code.

Shortcodes are small tags like `[store id="5"]` that your content editors embed in any text field. This plugin stores them in the database and renders them dynamically at runtime, with optional live data pulled from any database table.

---

## Features

- **Full CRUD UI** — list, create, edit, and delete shortcodes from the Filament panel
- **Attribute builder** — define named attributes with defaults via a repeater UI
- **HTML template editor** — write the output HTML with `{{attr}}`, `{{content}}`, and `{{db.column}}` placeholders
- **Dynamic data sources** — pull a live database row into any shortcode template at render time
- **Usage example** — auto-generated, copyable shortcode string shown in the table and edit form
- **Multi-tenancy** — first-class Filament multi-tenant support with auto-detection of `int`, `UUID`, and `ULID` primary keys
- **Zero boilerplate** — shortcodes register themselves automatically on boot; no manual `Shortcode::register()` calls needed

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.2 |
| Laravel | ^11.0 \| ^12.0 |
| Filament | ^3.0 |
| webwizo/laravel-shortcodes | ^1.0 |

---

## Installation

Install the package via Composer:

```bash
composer require webwizo/laravel-shortcodes-filament
```

Publish the config and migration:

```bash
php artisan vendor:publish --tag=shortcodes-filament-config
php artisan vendor:publish --tag=shortcodes-filament-migrations
php artisan migrate
```

Register the plugin in your Filament panel provider:

```php
use Webwizo\ShortcodesFilament\ShortcodesFilamentPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            ShortcodesFilamentPlugin::make(),
        ]);
}
```

---

## Usage

### Writing shortcodes in content

Once a shortcode is created in the panel, editors can embed it anywhere:

```
[store id="5"]Buy now[/store]

[hero class="dark" title="Welcome"]Homepage banner[/hero]
```

To render shortcodes in a Blade view, use the `webwizo/laravel-shortcodes` parser:

```blade
{!! Shortcode::compile($post->content) !!}
```

---

### Creating a shortcode in the panel

1. Navigate to **Content → Shortcodes** in the sidebar
2. Click **New Shortcode**
3. Fill in:
   - **Tag** — the shortcode name used in content, e.g. `store` (lowercase, hyphens allowed)
   - **Label** — human-readable name shown in the panel
   - **Attributes** — named parameters editors can pass, each with an optional default value
   - **Template** — the HTML output, using placeholders described below
4. Save — the shortcode is immediately live

---

### Template placeholders

| Placeholder | Replaced with |
|---|---|
| `{{content}}` | The inner content between opening and closing tags |
| `{{attr_name}}` | The value of a named attribute, falling back to its default |
| `{{db.column_name}}` | A column from the dynamic data source row |

**Example template:**

```html
<div class="store-card {{class}}">
    <h2>{{db.name}}</h2>
    <p>{{db.description}}</p>
    {{content}}
</div>
```

Used in content as:

```
[store id="5" class="featured"]Check it out[/store]
```

---

### Dynamic data sources

Enable **Dynamic Data Sources** on the plugin (on by default) to pull live data from any database table into a shortcode template.

In the **Dynamic Data Source** section of the edit form:

- **Database Table** — the table to query
- **Lookup Column** — the column used to find the row (e.g. `id`)
- **Shortcode Attribute** — which shortcode attribute holds the lookup value (e.g. `id`)

Then reference any column from that row in your template with `{{db.column_name}}`.

Results are cached for 600 seconds by default (configurable via `cache_ttl`).

---

## Configuration

After publishing, edit `config/shortcodes-filament.php`:

```php
return [

    // Eloquent model used for shortcodes. Swap this to extend with your own model.
    'model' => \Webwizo\ShortcodesFilament\Models\Shortcode::class,

    // Database table name
    'table_name' => 'shortcodes',

    // Tables hidden from the Dynamic Data Source picker
    'excluded_tables' => [
        'migrations', 'failed_jobs', 'sessions', 'cache', /* ... */
    ],

    // Cache TTL in seconds for dynamic data source results. Set 0 to disable.
    'cache_ttl' => 600,

    'tenant' => [
        // Foreign key column on the shortcodes table
        'foreign_key'  => 'team_id',

        // Relationship method name on the Shortcode model
        'relationship' => 'team',

        // Your tenant Eloquent model class
        'model'        => \App\Models\Team::class,

        // Primary-key type: 'int', 'uuid', 'ulid', 'string', or null to auto-detect
        'key_type'     => null,
    ],

];
```

---

## Plugin fluent API

All options can also be set inline in the panel provider, which takes precedence over the config file:

```php
ShortcodesFilamentPlugin::make()
    ->navigationGroup('CMS')
    ->navigationIcon('heroicon-o-code-bracket')
    ->navigationSort(5)
    ->usingDynamicDataSources(true)
```

---

## Multi-tenancy

The plugin has first-class support for Filament's [multi-tenancy](https://filamentphp.com/docs/3.x/panels/tenancy).

### Basic setup

Pass your tenant model and relationship details on the plugin:

```php
ShortcodesFilamentPlugin::make()
    ->scopedToTenant(true)
    ->tenantRelationship('team')       // method name on the Shortcode model
    ->tenantForeignKey('team_id')      // FK column on the shortcodes table
    ->tenantModel(\App\Models\Team::class)
```

### Tenant primary key types

The migration automatically uses the right column type for the foreign key based on your tenant model:

| Tenant key type | Column created |
|---|---|
| Auto-increment (`int`) | `UNSIGNED BIGINT` |
| UUID (`HasUuids`) | `UUID` (36 chars) |
| ULID (`HasUlids`) | `CHAR(26)` |
| Custom string | `VARCHAR` |

**Auto-detection** is on by default — the migration inspects `class_uses_recursive()` on your tenant model and picks the right column type. You can override it explicitly:

```php
// Via plugin fluent API
ShortcodesFilamentPlugin::make()
    ->tenantKeyType('ulid')

// Or via config
'tenant' => [
    'key_type' => 'ulid',
],
```

### Full multi-tenant example

```php
use App\Models\Team;
use Webwizo\ShortcodesFilament\ShortcodesFilamentPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->tenant(Team::class, slugAttribute: 'slug')
        ->plugins([
            ShortcodesFilamentPlugin::make()
                ->navigationGroup('Content')
                ->scopedToTenant(true)
                ->tenantRelationship('team')
                ->tenantForeignKey('team_id')
                ->tenantModel(Team::class),
        ]);
}
```

---

## Extending the model

You can swap in your own model by updating the config:

```php
// config/shortcodes-filament.php
'model' => \App\Models\Shortcode::class,
```

Your model should extend the package model to keep all functionality:

```php
namespace App\Models;

use Webwizo\ShortcodesFilament\Models\Shortcode as BaseShortcode;

class Shortcode extends BaseShortcode
{
    // Add your own methods, casts, relationships, etc.
}
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

---

## Contributing

Pull requests are welcome. For major changes please open an issue first to discuss what you would like to change.

---

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

---

## Credits

- [Asif Iqbal](https://github.com/webwizo)
- Built on [webwizo/laravel-shortcodes](https://github.com/webwizo/laravel-shortcodes) and [Filament](https://filamentphp.com)
