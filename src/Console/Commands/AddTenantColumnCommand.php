<?php

namespace Webwizo\ShortcodesFilament\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AddTenantColumnCommand extends Command
{
    protected $signature = 'shortcodes:add-tenant';

    protected $description = 'Generate a migration to add the tenant foreign key column to the shortcodes table.';

    public function handle(): int
    {
        $tenantModel      = config('shortcodes-filament.tenant.model');
        $tenantForeignKey = config('shortcodes-filament.tenant.foreign_key');
        $tableName        = config('shortcodes-filament.table_name', 'shortcodes');

        // ── Guard: tenant.model must be set ──────────────────────────────────
        if (! $tenantModel) {
            $this->error('tenant.model is not set in config/shortcodes-filament.php.');
            $this->line('Set it to your tenant Eloquent model class and re-run this command.');

            return self::FAILURE;
        }

        // ── Guard: tenant.foreign_key must be set ────────────────────────────
        if (! $tenantForeignKey) {
            $this->error('tenant.foreign_key is not set in config/shortcodes-filament.php.');
            $this->line('Set it to the FK column name (e.g. "team_id") and re-run this command.');

            return self::FAILURE;
        }

        // ── Guard: column must not already exist ─────────────────────────────
        if (Schema::hasColumn($tableName, $tenantForeignKey)) {
            $this->warn("Column [{$tenantForeignKey}] already exists on the [{$tableName}] table. Nothing to do.");

            return self::SUCCESS;
        }

        // ── Resolve key type ─────────────────────────────────────────────────
        $keyType = $this->resolveKeyType($tenantModel);

        // ── Generate migration file ──────────────────────────────────────────
        $timestamp = now()->format('Y_m_d_His');
        $className = 'AddTenantColumnToShortcodesTable';
        $fileName  = "{$timestamp}_add_tenant_column_to_{$tableName}_table.php";
        $path      = database_path("migrations/{$fileName}");

        $columnDefinition = $this->columnDefinition($keyType, $tenantForeignKey);

        $stub = $this->buildStub(
            className: $className,
            tableName: $tableName,
            foreignKey: $tenantForeignKey,
            columnDefinition: $columnDefinition,
        );

        file_put_contents($path, $stub);

        $this->info("Migration created: database/migrations/{$fileName}");
        $this->line("Detected key type : <comment>{$keyType}</comment>");
        $this->line("Foreign key column : <comment>{$tenantForeignKey}</comment>");
        $this->line("Table              : <comment>{$tableName}</comment>");
        $this->newLine();
        $this->line('Run <comment>php artisan migrate</comment> to apply the changes.');

        return self::SUCCESS;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function resolveKeyType(string $model): string
    {
        // 1. Explicit config value
        $configured = config('shortcodes-filament.tenant.key_type');
        if ($configured) {
            return $configured;
        }

        // 2. Auto-detect from model traits
        if (class_exists($model)) {
            $traits = class_uses_recursive($model);

            if (in_array(\Illuminate\Database\Eloquent\Concerns\HasUlids::class, $traits)) {
                return 'ulid';
            }

            if (in_array(\Illuminate\Database\Eloquent\Concerns\HasUuids::class, $traits)) {
                return 'uuid';
            }
        }

        return 'int';
    }

    protected function columnDefinition(string $keyType, string $foreignKey): string
    {
        return match ($keyType) {
            'ulid'   => "\$table->char('{$foreignKey}', 26)->nullable()->index()->after('id')",
            'uuid'   => "\$table->uuid('{$foreignKey}')->nullable()->index()->after('id')",
            'string' => "\$table->string('{$foreignKey}')->nullable()->index()->after('id')",
            default  => "\$table->unsignedBigInteger('{$foreignKey}')->nullable()->index()->after('id')",
        };
    }

    protected function buildStub(
        string $className,
        string $tableName,
        string $foreignKey,
        string $columnDefinition,
    ): string {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
            {$columnDefinition};

            // Drop the global unique index on tag and replace with a
            // tenant-scoped one so the same tag can exist per tenant.
            \$table->dropUnique(['{$tableName}_tag_unique']);
            \$table->unique(['{$foreignKey}', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
            \$table->dropUnique(['{$foreignKey}', 'tag']);
            \$table->dropIndex(['{$foreignKey}']);
            \$table->dropColumn('{$foreignKey}');
            \$table->unique('tag');
        });
    }
};
PHP;
    }
}
