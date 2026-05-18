<?php

namespace Webwizo\ShortcodesFilament\Resources;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use Webwizo\ShortcodesFilament\Resources\ShortcodeResource\Pages;
use Webwizo\ShortcodesFilament\ShortcodesFilamentPlugin;

class ShortcodeResource extends Resource
{
    protected static ?string $slug = 'shortcodes';

    /**
     * Filament reads this static property to determine which relationship to
     * use for tenant ownership checks. We override getTenantOwnershipRelationshipName()
     * below so it always reads from config at call-time rather than relying on a
     * static property that may not have been populated yet when Filament boots.
     */
    protected static ?string $tenantOwnershipRelationshipName = null;

    /**
     * Called by the ServiceProvider after config is loaded
     * to inject the configured relationship name into this static property.
     */
    public static function setTenantOwnershipRelationshipName(string $name): void
    {
        static::$tenantOwnershipRelationshipName = $name;
    }

    /**
     * Override Filament's default so the relationship name is always read
     * from config at call-time. This prevents the "does not have a relationship
     * named [vendor]" error that occurs when Filament checks tenancy before the
     * ServiceProvider has had a chance to set the static property.
     */
    public static function getTenantOwnershipRelationshipName(): string
    {
        return static::$tenantOwnershipRelationshipName
            ?? config('shortcodes-filament.tenant.relationship', 'vendor');
    }

    public static function isScopedToTenant(): bool
    {
        return static::getPlugin()->isScopedToTenant();
    }

    public static function getModel(): string
    {
        return config('shortcodes-filament.model');
    }

    public static function getNavigationIcon(): string
    {
        return static::getPlugin()->getNavigationIcon();
    }

    public static function getNavigationGroup(): ?string
    {
        return static::getPlugin()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return static::getPlugin()->getNavigationSort();
    }

    protected static function getPlugin(): ShortcodesFilamentPlugin
    {
        return Filament::getCurrentPanel()->getPlugin('laravel-shortcodes');
    }

    public static function form(Form $form): Form
    {
        $hasDynamicSources = static::getPlugin()->hasDynamicDataSources();

        return $form->schema([

            Forms\Components\Section::make('Identity')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('tag')
                        ->label('Tag Name')
                        ->required()
                        ->unique(
                            table: config('shortcodes-filament.model'),
                            ignoreRecord: true
                        )
                        ->regex('/^[a-z][a-z0-9\-]*$/')
                        ->helperText('Lowercase letters, numbers, hyphens only. e.g. store, blog-post')
                        ->maxLength(64),

                    Forms\Components\TextInput::make('label')
                        ->label('Display Label')
                        ->required()
                        ->maxLength(128),

                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Shortcode Attributes')
                ->description('Attributes users can pass inside the shortcode tag.')
                ->schema([
                    Forms\Components\Repeater::make('attributes')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Name')
                                ->required()
                                ->placeholder('class'),

                            Forms\Components\TextInput::make('default')
                                ->label('Default Value')
                                ->placeholder('btn-primary'),

                            Forms\Components\TextInput::make('description')
                                ->label('Description')
                                ->placeholder('Optional CSS classes'),
                        ])
                        ->columns(3)
                        ->addActionLabel('Add Attribute')
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => $state['name'] ?? 'New Attribute'),
                ]),

            Forms\Components\Section::make('Dynamic Data Source')
                ->description('Pull live data from any DB table. Use {{db.column_name}} in your template.')
                ->icon('heroicon-o-circle-stack')
                ->visible($hasDynamicSources)
                ->schema([
                    Forms\Components\Select::make('data_source_table')
                        ->label('Database Table')
                        ->options(fn () => static::getTableOptions())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set): void {
                            $set('data_source_key', null);
                            $set('data_source_attr', null);
                        })
                        ->placeholder('None — static shortcode only'),

                    Forms\Components\Select::make('data_source_key')
                        ->label('Lookup Column (in DB)')
                        ->helperText('Column used to find the row. Usually "id".')
                        ->options(fn (Get $get) => static::getColumnOptions($get('data_source_table')))
                        ->searchable()
                        ->live()
                        ->visible(fn (Get $get): bool => filled($get('data_source_table'))),

                    Forms\Components\TextInput::make('data_source_attr')
                        ->label('Shortcode Attribute Name')
                        ->helperText('Which shortcode attribute holds the lookup value. e.g. "id" → [store id="5"]')
                        ->placeholder('id')
                        ->visible(fn (Get $get): bool => filled($get('data_source_table'))),

                    Forms\Components\Placeholder::make('available_columns')
                        ->label('Available {{db.*}} Placeholders')
                        ->content(fn (Get $get): string => static::getColumnPlaceholders($get('data_source_table')))
                        ->visible(fn (Get $get): bool => filled($get('data_source_table'))),
                ]),

            Forms\Components\Section::make('HTML Template')
                ->description('{{content}} = inner content · {{attr}} = shortcode attribute · {{db.col}} = database value')
                ->schema([
                    Forms\Components\Textarea::make('template')
                        ->label('')
                        ->required()
                        ->rows(12)
                        ->columnSpanFull()
                        ->placeholder(
                            "<div class=\"store {{class}}\">\n" .
                            "    <strong>{{db.name}}</strong>\n" .
                            "    {{content}}\n" .
                            "</div>"
                        ),

                    Forms\Components\Placeholder::make('usage_example')
                        ->label('Usage Example')
                        ->content(fn ($record) => $record?->usage_example ?? 'Save to see usage example.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tag')
                    ->label('Tag')
                    ->badge()
                    ->color('primary')
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usage_example')
                    ->label('Usage')
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->color('gray')
                    ->copyable()
                    ->copyMessage('Shortcode copied!')
                    ->copyMessageDuration(1500)
                    ->limit(60)
                    ->tooltip(fn ($record) => $record?->usage_example),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('data_source_table')
                    ->label('Data Source')
                    ->badge()
                    ->color('warning')
                    ->placeholder('Static')
                    ->toggleable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShortcodes::route('/'),
            'create' => Pages\CreateShortcode::route('/create'),
            'edit'   => Pages\EditShortcode::route('/{record}/edit'),
        ];
    }

    protected static function getTableOptions(): array
    {
        $excluded = config('shortcodes-filament.excluded_tables', []);

        return collect(Schema::getTables())
            ->pluck('name')
            ->reject(fn ($t) => in_array($t, $excluded))
            ->mapWithKeys(fn ($t) => [
                $t => str($t)->replace('_', ' ')->title()->toString(),
            ])
            ->toArray();
    }

    protected static function getColumnOptions(?string $table): array
    {
        if (! $table) {
            return [];
        }

        return collect(Schema::getColumnListing($table))
            ->mapWithKeys(fn ($c) => [$c => $c])
            ->toArray();
    }

    protected static function getColumnPlaceholders(?string $table): string
    {
        if (! $table) {
            return '—';
        }

        return collect(Schema::getColumnListing($table))
            ->map(fn ($c) => '{{db.' . $c . '}}')
            ->join('   ');
    }
}