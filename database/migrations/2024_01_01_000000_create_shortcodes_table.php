<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function tableName(): string
    {
        return config('shortcodes-filament.table_name', 'shortcodes');
    }

    protected function resolveTenantKeyType(): string
    {
        // 1. Explicit value in published config
        $configured = config('shortcodes-filament.tenant.key_type');
        if ($configured) {
            return $configured;
        }

        // 2. Auto-detect from the tenant model's traits
        $model = config('shortcodes-filament.tenant.model');
        if ($model && class_exists($model)) {
            $traits = class_uses_recursive($model);

            if (in_array(\Illuminate\Database\Eloquent\Concerns\HasUlids::class, $traits)) {
                return 'ulid';
            }

            if (in_array(\Illuminate\Database\Eloquent\Concerns\HasUuids::class, $traits)) {
                return 'uuid';
            }
        }

        // 3. Default: standard auto-incrementing integer
        return 'int';
    }

    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();

            $tenantModel      = config('shortcodes-filament.tenant.model');
            $tenantForeignKey = config('shortcodes-filament.tenant.foreign_key') ?: 'vendor_id';

            // Only create the foreign key column when a tenant model is configured.
            // Users without multi-tenancy get a clean table with no unused columns.
            if ($tenantModel) {
                $keyType = $this->resolveTenantKeyType();

                match ($keyType) {
                    'ulid'   => $table->char($tenantForeignKey, 26)->nullable()->index(),
                    'uuid'   => $table->uuid($tenantForeignKey)->nullable()->index(),
                    'string' => $table->string($tenantForeignKey)->nullable()->index(),
                    default  => $table->unsignedBigInteger($tenantForeignKey)->nullable()->index(),
                };
            }

            $table->string('tag');
            $table->string('label');
            $table->text('description')->nullable();
            $table->longText('template');
            $table->json('attributes')->nullable();
            $table->enum('type', ['static', 'dynamic'])->default('static');
            $table->string('data_source_table')->nullable();
            $table->string('data_source_key')->nullable();
            $table->string('data_source_attr')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint scoped to tenant when multi-tenancy is enabled,
            // otherwise just unique by tag globally.
            if ($tenantModel) {
                $table->unique([$tenantForeignKey, 'tag']);
            } else {
                $table->unique('tag');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName());
    }
};