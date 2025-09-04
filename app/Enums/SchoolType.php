<?php

namespace App\Enums;

enum SchoolType: string
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case COMBINED = 'combined';
    case SPECIAL = 'special';
    case TECHNICAL = 'technical';
}