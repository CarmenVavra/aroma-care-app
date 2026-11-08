<?php

namespace App\Enums;

enum StockStatus: string
{
    case Full = 'full';
    case Medium = 'medium';
    case Low = 'low';

    /**
     * Derive the status from a fill ratio between 0 and 1.
     * Thresholds per design handoff: >= 50 % full, 20–49 % medium, < 20 % low.
     */
    public static function fromRatio(float $ratio): self
    {
        return match (true) {
            $ratio >= 0.5 => self::Full,
            $ratio >= 0.2 => self::Medium,
            default => self::Low,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Voll',
            self::Medium => 'Mittel',
            self::Low => 'Fast leer',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Full => 'bg-[#DCE5FB] text-[#1E3A8A] dark:bg-cobalt-500/25 dark:text-cobalt-200',
            self::Medium => 'bg-[#D6EEF9] text-[#14507A] dark:bg-sky-300/20 dark:text-[#A8DEF3]',
            self::Low => 'bg-[#DCCFF3] text-[#3F1F85] dark:bg-violet-500/30 dark:text-[#CDB7F5]',
        };
    }

    public function barClasses(): string
    {
        return match ($this) {
            self::Full => 'bg-cobalt-500',
            self::Medium => 'bg-sky-300',
            self::Low => 'bg-violet-500',
        };
    }
}
