# Changelog

All notable changes to `laravel-shortcodes-filament` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.1] — 2026-05-19

### Added

- **Shortcode type** — shortcodes now have a `type` field (`static` or `dynamic`). Static shortcodes show only the attribute builder and template editor. Dynamic shortcodes reveal the Database Data Source section. Existing records default to `static`.
- **`type` column migration** — `type` enum column added to the `shortcodes` table (fresh installs pick it up automatically from the published migration).
- **Type badge in table** — the list view now shows a `Static` / `Dynamic` badge per shortcode with a type filter.
- **Annotated Facade** — `Webwizo\ShortcodesFilament\Facades\Shortcode` with full `@method` docblocks for `compile()`, `strip()`, `register()`, `enable()`, and `disable()` for IDE autocompletion.

### Fixed

- **`{{attr}}` rendering empty attributes** — attributes with no value passed and no default are now excluded from `{{attr}}` output, preventing `id=""` appearing in HTML.
- **Dynamic data source lookup** — `resolveDataSource()` was calling `$shortcode->get($attr)` which returns a formatted HTML attribute string (e.g. `id="5"`) instead of the raw value. Changed to `$shortcode->{$attr}` so the correct value is used for the DB query.
- **Missing lookup attribute** — if the lookup attribute is not provided in the shortcode tag (e.g. `[store]` with no `id`), the shortcode now returns an empty string instead of rendering a broken template with raw `{{db.*}}` placeholders.
- **Invalid lookup value** — if the DB query returns no row (invalid or non-existent ID), the shortcode returns an empty string instead of an empty wrapper element.
- **Soft-deleted records** — dynamic data source queries now automatically exclude soft-deleted rows by adding `whereNull('deleted_at')` when the target table has that column.
- **Usage example for dynamic shortcodes** — the auto-generated usage example now prepends the lookup attribute (e.g. `id=""`) for dynamic shortcodes so editors know the required attribute.

---

## [1.0.0] — 2026-05-19

### Added

- Initial release of `webwizo/laravel-shortcodes-filament`
- **Filament v3 resource** — full CRUD UI for creating, editing, and deleting shortcodes from the admin panel
- **Attribute builder** — define named shortcode attributes with default values via a Filament Repeater
- **HTML template editor** — write shortcode output HTML using `{{attr}}`, `{{content}}`, and `{{db.column}}` placeholders
- **Dynamic data sources** — attach any database table to a shortcode and pull a live row into the template at render time using a configurable lookup column and attribute
- **Result caching** — dynamic data source rows are cached (default 600 seconds, configurable via `cache_ttl`)
- **Auto-registration** — active shortcodes are registered with the `webwizo/laravel-shortcodes` compiler automatically on boot and whenever a shortcode is saved or deleted; no manual `Shortcode::register()` calls required
- **Usage example column** — auto-generated, copyable shortcode string displayed in the table list and edit form
- **Multi-tenancy support** — first-class integration with Filament's multi-tenant panels via `scopedToTenant`, `tenantRelationship`, `tenantForeignKey`, and `tenantModel` plugin options
- **Tenant foreign key auto-detection** — migration inspects `class_uses_recursive()` on the tenant model and creates the correct column type (`UNSIGNED BIGINT`, `UUID`, or `CHAR(26)`) without any manual configuration
- **Explicit key type override** — `->tenantKeyType('ulid'|'uuid'|'int'|'string')` on the plugin or `tenant.key_type` in config for cases where auto-detection is not possible
- **Fluent plugin API** — all options (`navigationGroup`, `navigationIcon`, `navigationSort`, `usingDynamicDataSources`, tenant settings) configurable inline in the panel provider
- **Publishable config** — `vendor:publish --tag=shortcodes-filament-config`
- **Publishable migration** — `vendor:publish --tag=shortcodes-filament-migrations`
- **Model swapping** — replace the built-in `Shortcode` model with your own via `config('shortcodes-filament.model')`
- **Conditional FK column** — foreign key column and tenant-scoped unique index are only added to the migration when `tenant.model` is configured; non-tenant installs get a clean table with no unused columns
- **`php artisan shortcodes:add-tenant` command** — generates a migration to add the tenant FK column to an existing table when upgrading a non-tenant install to multi-tenancy; guards against missing config and already-existing columns
- Support for PHP ^8.2, Laravel ^11.0 | ^12.0, Filament ^3.0

[1.0.1]: https://github.com/webwizo/laravel-shortcodes-filament/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/webwizo/laravel-shortcodes-filament/releases/tag/v1.0.0
