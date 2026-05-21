<?php

// Filament v5 moved/renamed several classes. This file creates class aliases so that
// ShortcodeResource can use the v3/v4 names (Form, Section, Get, Set) on all versions.
// Loaded via composer autoload "files" before any class declaration is processed.

if (class_exists(\Filament\Schemas\Schema::class)) {
    // Form class renamed to Schema
    if (! class_exists(\Filament\Forms\Form::class)) {
        class_alias(\Filament\Schemas\Schema::class, \Filament\Forms\Form::class);
    }

    // Section moved from Forms to Schemas
    if (! class_exists(\Filament\Forms\Components\Section::class)) {
        class_alias(\Filament\Schemas\Components\Section::class, \Filament\Forms\Components\Section::class);
    }

    // Get/Set moved from Forms to Schemas\Components\Utilities
    if (! class_exists(\Filament\Forms\Get::class)) {
        class_alias(\Filament\Schemas\Components\Utilities\Get::class, \Filament\Forms\Get::class);
    }

    if (! class_exists(\Filament\Forms\Set::class)) {
        class_alias(\Filament\Schemas\Components\Utilities\Set::class, \Filament\Forms\Set::class);
    }
}

// Table actions moved from Tables\Actions to Actions (flat namespace)
if (class_exists(\Filament\Actions\EditAction::class)) {
    if (! class_exists(\Filament\Tables\Actions\EditAction::class)) {
        class_alias(\Filament\Actions\EditAction::class, \Filament\Tables\Actions\EditAction::class);
    }

    if (! class_exists(\Filament\Tables\Actions\DeleteAction::class)) {
        class_alias(\Filament\Actions\DeleteAction::class, \Filament\Tables\Actions\DeleteAction::class);
    }

    if (! class_exists(\Filament\Tables\Actions\BulkActionGroup::class)) {
        class_alias(\Filament\Actions\BulkActionGroup::class, \Filament\Tables\Actions\BulkActionGroup::class);
    }

    if (! class_exists(\Filament\Tables\Actions\DeleteBulkAction::class)) {
        class_alias(\Filament\Actions\DeleteBulkAction::class, \Filament\Tables\Actions\DeleteBulkAction::class);
    }
}
