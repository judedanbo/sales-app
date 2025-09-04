<?php

namespace App\Enums;

enum MediumOfInstruction: string
{
    case ENGLISH = 'english';
    case HINDI = 'hindi';
    case REGIONAL = 'regional';
    case BILINGUAL = 'bilingual';

    public function label(): string
    {
        return match ($this) {
            self::ENGLISH => 'English',
            self::HINDI => 'Hindi',
            self::REGIONAL => 'Regional Language',
            self::BILINGUAL => 'Bilingual',
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
