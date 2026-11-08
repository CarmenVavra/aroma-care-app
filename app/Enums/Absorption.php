<?php

namespace App\Enums;

enum Absorption: string
{
    case Slow = 'slow';
    case Medium = 'medium';
    case Fast = 'fast';

    public function label(): string
    {
        return match ($this) {
            self::Slow => 'Langsam',
            self::Medium => 'Mittel',
            self::Fast => 'Schnell',
        };
    }

    /**
     * Needle angle for the speed gauge component.
     */
    public function gaugeAngle(): int
    {
        return match ($this) {
            self::Slow => -60,
            self::Medium => 0,
            self::Fast => 60,
        };
    }
}
