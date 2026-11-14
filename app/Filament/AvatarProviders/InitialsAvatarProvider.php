<?php

namespace App\Filament\AvatarProviders;

use App\Models\User;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Tekent de initialen lokaal als SVG-data-URI, zodat er geen naam naar een externe avatardienst gaat.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = $record instanceof User ? $record->initials : '';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="#e0e7ff"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="sans-serif" font-size="26" font-weight="600" fill="#4338ca">'
            .htmlspecialchars($initials, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            .'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
