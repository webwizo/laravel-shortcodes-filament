# Changelog

All notable changes to `laravel-shortcodes-filament` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
- Support for PHP ^8.2, Laravel ^11.0 | ^12.0, Filament ^3.0

[1.0.0]: https://github.com/webwizo/laravel-shortcodes-filament/releases/tag/v1.0.0
