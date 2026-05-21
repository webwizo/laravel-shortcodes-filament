<?php

namespace Webwizo\ShortcodesFilament\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Shortcode extends Model
{
    protected $fillable = [
        'tag',
        'label',
        'description',
        'type',
        'template',
        'attributes',
        'is_active',
        'data_source_table',
        'data_source_key',
        'data_source_attr',
    ];

    protected $casts = [
        'attributes' => 'json',
        'is_active'  => 'boolean',
    ];

    public function isStatic(): bool
    {
        return $this->type === 'static' || ! $this->type;
    }

    public function isDynamic(): bool
    {
        return $this->type === 'dynamic';
    }

public function getTable(): string
    {
        return config('shortcodes-filament.table_name', 'shortcodes');
    }

    public function render(object $shortcode, ?string $content): string
    {
        $output = $this->template;

        $output = str_replace('{{content}}', $content ?? '', $output);

        // 'attributes' conflicts with Eloquent's internal $attributes property.
        // Use getAttributeValue() to correctly retrieve the cast JSON column value.
        $shortcodeAttrs = $this->getAttributeValue('attributes') ?? [];

        // {{attr}} only renders attributes that are explicitly defined in the
        // shortcode's attributes list. This keeps templates predictable and
        // prevents arbitrary attribute injection from the tag itself.
        $allAttrs = [];

        foreach ($shortcodeAttrs as $attr) {
            if (! is_array($attr) || empty($attr['name'])) {
                continue;
            }

            $name     = $attr['name'];
            $rawValue = $shortcode->{$name} ?? ($attr['default'] ?? '');

            // Replace individual {{attr_name}} placeholders
            $output = str_replace('{{' . $name . '}}', e($rawValue), $output);

            // Only include in {{attr}} if the value is non-empty
            if ($rawValue !== '' && $rawValue !== null) {
                $allAttrs[] = $name . '="' . e($rawValue) . '"';
            }
        }

        // Replace {{attr}} with all defined attributes combined e.g. class="bold" id="foo"
        $output = str_replace('{{attr}}', implode(' ', $allAttrs), $output);

        if ($this->isDynamic() && $this->data_source_table) {
            // If the lookup attribute wasn't provided in the tag, bail early
            // rather than rendering a broken template with raw {{db.*}} placeholders.
            $lookupValue = $shortcode->{$this->data_source_attr} ?? null;
            if (! filled($lookupValue)) {
                return '';
            }

            $row = $this->resolveDataSource($shortcode);

            // If no row found for the given lookup value, bail early.
            if (! $row) {
                return '';
            }

            foreach ((array) $row as $column => $value) {
                $output = str_replace('{{db.' . $column . '}}', e($value ?? ''), $output);
            }

            // Strip any remaining {{db.*}} placeholders that had no matching column.
            $output = preg_replace('/\{\{db\.[^}]+\}\}/', '', $output);
        }

        return $output;
    }

    protected function resolveDataSource(object $shortcode): ?object
    {
        $table       = $this->data_source_table;
        $key         = $this->data_source_key;
        $attr        = $this->data_source_attr;
        $lookupValue = $shortcode->{$attr};

        if (! $table || ! $key || ! $attr || ! $lookupValue) {
            return null;
        }

        $ttl      = config('shortcodes-filament.cache_ttl', 600);
        $cacheKey = "shortcode_ds_{$table}_{$key}_{$lookupValue}";

        $query = fn () => $this->buildDataSourceQuery($table, $key, $lookupValue)->first();

        if ($ttl > 0) {
            return Cache::remember($cacheKey, now()->addSeconds($ttl), $query);
        }

        return $query();
    }

    protected function buildDataSourceQuery(string $table, string $key, mixed $lookupValue): \Illuminate\Database\Query\Builder
    {
        $query = DB::table($table)->where($key, $lookupValue);

        // Respect soft deletes — exclude rows where deleted_at is set.
        if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    public function getUsageExampleAttribute(): string
    {
        $attrs = collect($this->getAttributeValue('attributes') ?? [])
            ->filter(fn ($a) => is_array($a) && filled($a['name'] ?? ''))
            ->map(fn ($a) => $a['name'] . '="' . ($a['default'] ?? '') . '"')
            ->all();

        // For dynamic shortcodes, prepend the lookup attribute (e.g. id="")
        // since it lives in data_source_attr, not the repeater.
        if ($this->isDynamic() && filled($this->data_source_attr)) {
            array_unshift($attrs, $this->data_source_attr . '=""');
        }

        $tag      = $this->tag;
        $attrStr  = implode(' ', $attrs);

        return $attrStr
            ? "[{$tag} {$attrStr}]content[/{$tag}]"
            : "[{$tag}]content[/{$tag}]";
    }
}