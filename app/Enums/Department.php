<?php

namespace App\Enums;

enum Department: string
{
    case Sales = 'Sales';
    case PreSales = 'Presales';
    case IT = 'IT';
    case Finance = 'Finance';
    case Technician = 'Technician';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the label for display.
     */
    public function label(): string
    {
        return $this->value;
    }
}
