<?php

namespace Webwizo\ShortcodesFilament\Resources\ShortcodeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Webwizo\ShortcodesFilament\Resources\ShortcodeResource;

class EditShortcode extends EditRecord
{
    protected static string $resource = ShortcodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}