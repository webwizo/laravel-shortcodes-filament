<?php

namespace Webwizo\ShortcodesFilament\Resources\ShortcodeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webwizo\ShortcodesFilament\Resources\ShortcodeResource;

class ListShortcodes extends ListRecords
{
    protected static string $resource = ShortcodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}