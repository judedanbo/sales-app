<?php

namespace App\Enums;

enum ManagementType: string
{
    case PRIVATE = 'private';
    case GOVERNMENT = 'government';
    case TRUST = 'trust';
    case SOCIETY = 'society';
}