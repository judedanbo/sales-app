<?php

namespace App\Enums;

enum BoardAffiliation: string
{
    case CBSE = 'cbse';
    case ICSE = 'icse';
    case STATE_BOARD = 'state_board';
    case IB = 'ib';
    case CAMBRIDGE = 'cambridge';

    public function label(): string
    {
        return match ($this) {
            self::CBSE => 'CBSE',
            self::ICSE => 'ICSE',
            self::STATE_BOARD => 'State Board',
            self::IB => 'IB',
            self::CAMBRIDGE => 'Cambridge',
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
