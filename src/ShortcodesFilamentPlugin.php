<?php

namespace Webwizo\ShortcodesFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Webwizo\ShortcodesFilament\Models\Shortcode;
use Webwizo\ShortcodesFilament\Resources\ShortcodeResource;

class ShortcodesFilamentPlugin implements Plugin
{
    protected bool $usingDynamicDataSources = true;
    protected string $navigationGroup = 'Content';
    protected string $navigationIcon = 'heroicon-o-code-bracket';
    protected ?int $navigationSort = null;
    protected bool $scopedToTenant = false;

    // Tenant overrides (fall back to config if not set)
    protected ?string $tenantForeignKey = null;
    protected ?string $tenantRelationship = null;
    protected ?string $tenantModel = null;

    /** 'int' | 'uuid' | 'ulid' | 'string' | null (auto-detect) */
    protected ?string $tenantKeyType = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'laravel-shortcodes';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ShortcodeResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        $relationship = $this->getTenantRelationship();
        $foreignKey   = $this->getTenantForeignKey();
        $tenantModel  = $this->getTenantModel();

        if ($relationship && $tenantModel) {
            // Register the Eloquent relationship resolver on the model using the
            // values from the fluent plugin API (set in the PanelProvider).
            // This must happen here — in plugin boot() — because this is the
            // earliest point where both (a) the panel's plugin config is resolved
            // and (b) we can register things before Filament's tenancy checks run.
            Shortcode::resolveRelationUsing(
                $relationship,
                fn (Shortcode $model) => $model->belongsTo($tenantModel, $foreignKey)
            );

            // Sync the static property so Resource::getTenantOwnershipRelationshipName()
            // returns the correct value without needing to fall back to config.
            ShortcodeResource::setTenantOwnershipRelationshipName($relationship);
        }
    }

    // ── Fluent API ────────────────────────────────────────────────────────────

    public function usingDynamicDataSources(bool $condition = true): static
    {
        $this->usingDynamicDataSources = $condition;

        return $this;
    }

    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationIcon(string $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function scopedToTenant(bool $condition = true): static
    {
        $this->scopedToTenant = $condition;

        return $this;
    }

    /**
     * The foreign key column on the shortcodes table.
     * e.g. ->tenantForeignKey('vendor_id')
     */
    public function tenantForeignKey(string $column): static
    {
        $this->tenantForeignKey = $column;

        return $this;
    }

    /**
     * The relationship method name defined on the Shortcode model.
     * e.g. ->tenantRelationship('vendor')
     */
    public function tenantRelationship(string $relationship): static
    {
        $this->tenantRelationship = $relationship;

        return $this;
    }

    /**
     * The Eloquent model class for the tenant.
     * e.g. ->tenantModel(\App\Models\Vendor::class)
     */
    public function tenantModel(string $model): static
    {
        $this->tenantModel = $model;

        return $this;
    }

    /**
     * Explicitly set the tenant primary-key type used for the foreign key column.
     * Only needed if auto-detection gives the wrong result.
     *
     * Supported: 'int', 'uuid', 'ulid', 'string'
     *
     * e.g. ->tenantKeyType('ulid')
     */
    public function tenantKeyType(string $type): static
    {
        $this->tenantKeyType = $type;

        return $this;
    }

    // ── Getters ───────────────────────────────────────────────────────────────

    public function hasDynamicDataSources(): bool
    {
        return $this->usingDynamicDataSources;
    }

    public function getNavigationGroup(): string
    {
        return $this->navigationGroup;
    }

    public function getNavigationIcon(): string
    {
        return $this->navigationIcon;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function isScopedToTenant(): bool
    {
        return $this->scopedToTenant;
    }

    public function getTenantForeignKey(): string
    {
        return $this->tenantForeignKey
            ?? config('shortcodes-filament.tenant.foreign_key', 'vendor_id');
    }

    public function getTenantRelationship(): string
    {
        return $this->tenantRelationship
            ?? config('shortcodes-filament.tenant.relationship', 'vendor');
    }

    public function getTenantModel(): ?string
    {
        return $this->tenantModel
            ?? config('shortcodes-filament.tenant.model');
    }

    /**
     * Resolve the tenant key type, using:
     *   1. The value set via ->tenantKeyType() on the plugin
     *   2. The 'tenant.key_type' config value
     *   3. Auto-detection from the tenant model's traits (HasUlids → 'ulid', HasUuids → 'uuid')
     *   4. Falls back to 'int'
     *
     * @return 'int'|'uuid'|'ulid'|'string'
     */
    public function getTenantKeyType(): string
    {
        // 1. Explicit override on the plugin instance
        if ($this->tenantKeyType) {
            return $this->tenantKeyType;
        }

        // 2. Explicit override in published config
        $configType = config('shortcodes-filament.tenant.key_type');
        if ($configType) {
            return $configType;
        }

        // 3. Auto-detect from the tenant model's traits
        $model = $this->getTenantModel();

        if ($model && class_exists($model)) {
            $traits = class_uses_recursive($model);

            if (in_array(\Illuminate\Database\Eloquent\Concerns\HasUlids::class, $traits)) {
                return 'ulid';
            }

            if (in_array(\Illuminate\Database\Eloquent\Concerns\HasUuids::class, $traits)) {
                return 'uuid';
            }
        }

        // 4. Default: standard auto-incrementing integer
        return 'int';
    }
}