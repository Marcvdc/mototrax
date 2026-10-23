<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MotorType: string implements HasLabel
{
    case Naked = 'naked';
    case Sport = 'sport';
    case Toer = 'toer';
    case Adventure = 'adventure';
    case Cruiser = 'cruiser';
    case Enduro = 'enduro';
    case Klassiek = 'klassiek';
    case Scooter = 'scooter';

    public function getLabel(): string
    {
        return $this->name;
    }
}
