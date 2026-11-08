<?php

namespace App\Enums;

enum RecipeCategory: string
{
    case Sleep = 'sleep';
    case Respiratory = 'respiratory';
    case Focus = 'focus';
    case Relief = 'relief';
    case Skin = 'skin';
    case Hair = 'hair';
    case Room = 'room';

    public function label(): string
    {
        return match ($this) {
            self::Sleep => 'Schlaf & Entspannung',
            self::Respiratory => 'Atemwege',
            self::Focus => 'Konzentration',
            self::Relief => 'Kopf & Muskeln',
            self::Skin => 'Haut & Pflege',
            self::Hair => 'Haare',
            self::Room => 'Raumduft',
        };
    }
}
