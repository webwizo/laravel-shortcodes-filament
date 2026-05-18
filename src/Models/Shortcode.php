<?php

namespace Webwizo\ShortcodesFilament\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Shortcode extends Model
{
    protected $fillable = [
        'tag',
        'label',
        'description',
        'template',
        'attributes',
        'is_active',
        'data_source_table',
        'data_source_key',
        'data_source_attr',
    ];

    protected $casts = [
        'attributes' => 'array',
        'is_active'  => 'boolean',
    ];

public function getTable(): string
    {
        return config('shortcodes-filament.table_name', 'shortcodes');
    }

    public function render(object $shortcode, ?string $content): string
    {
        $output = $this->template;

        $output = str_replace('{{content}}', $content ?? '', $output);

        foreach ($this->attributes ?? [] as $attr) {
            $name    = $attr['name'];
            $default = $attr['default'] ?? '';
            $value   = $shortcode->get($name, $default);
            $output  = str_replace('{{' . $name . '}}', e($value), $output);
        }

        if ($this->data_source_table) {
            $row = $this->resolveDataSource($shortcode);

            if ($row) {
                foreach ((array) $row as $column => $value) {
                    $output = str_replace('{{db.' . $column . '}}', e($value ?? ''), $output);
                }
            }
        }

        return $output;
    }

    protected function resolveDataSource(object $shortcode): ?object
    {
        $table       = $this->data_source_table;
        $key         = $this->data_source_key;
        $attr        = $this->data_source_attr;
        $lookupValue = $shortcode->get($attr);

        if (! $table || ! $key || ! $attr || ! $lookupValue) {
            return null;
        }

        $ttl      = config('shortcodes-filament.cache_ttl', 600);
        $cacheKey = "shortcode_ds_{$table}_{$key}_{$lookupValue}";

        if ($ttl > 0) {
            return Cache::remember(
                $cacheKey,
                now()->addSeconds($ttl),
                fn () => DB::table($table)->where($key, $lookupValue)->first()
            );
        }

        return DB::table($table)->where($key, $lookupValue)->first();
    }

    public function getUsageExampleAttribute(): string
    {
        $attrs = collect($this->attributes ?? [])
            ->filter(fn ($a) => filled($a['name'] ?? ''))
            ->map(fn ($a) => $a['name'] . '="' . ($a['default'] ?? '') . '"')
            ->implode(' ');

        $tag = $this->tag;

        return $attrs
            ? "[{$tag} {$attrs}]content[/{$tag}]"
            : "[{$tag}]content[/{$tag}]";
    }
}